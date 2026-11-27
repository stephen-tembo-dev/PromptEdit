<?php

namespace Tests\Feature\Http\Generations;

use App\Enums\Billing\LedgerEntryType;
use App\Enums\User\PermissionName;
use App\Jobs\Generations\DispatchWaitingGenerations;
use App\Models\Billing\CreditPurchase;
use App\Models\Generations\Generation;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class GenerationControllerTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    public function test_guests_are_sent_to_log_in(): void
    {
        $this->get('/generations')->assertRedirect('/login');
        $this->get('/generations/create')->assertRedirect('/login');
    }

    public function test_the_history_lists_only_the_users_own_generations(): void
    {
        $user = User::factory()->create();
        $mine = Generation::factory()->for($user)->processing()->create();
        Generation::factory()->succeeded()->create();

        $this->actingAs($user)
            ->get('/generations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('generations/Index')
                ->has('generations.data', 1)
                ->where('generations.data.0.id', $mine->uuid)
                ->where('generations.data.0.actual_charge', null)
                ->where('hasUnfinished', true));
    }

    public function test_scrolling_loads_the_next_page_of_older_generations(): void
    {
        $user = User::factory()->create();
        $oldest = Generation::factory()->for($user)->succeeded()->create(['created_at' => now()->subDay()]);
        Generation::factory()->for($user)->succeeded()->count(12)->create();

        $firstPage = $this->actingAs($user)->get('/generations');

        $firstPage->assertInertia(fn (Assert $page) => $page->has('generations.data', 12));
        $this->assertSame(2, $firstPage->viewData('page')['scrollProps']['generations']['nextPage']);

        $secondPage = $this->actingAs($user)->get('/generations?page=2');

        $secondPage->assertInertia(fn (Assert $page) => $page
            ->has('generations.data', 1)
            ->where('generations.data.0.id', $oldest->uuid));
        $this->assertNull($secondPage->viewData('page')['scrollProps']['generations']['nextPage']);
    }

    public function test_a_live_refresh_puts_the_first_page_back_on_top_matched_by_id(): void
    {
        $user = User::factory()->create();
        Generation::factory()->for($user)->processing()->create();
        $version = $this->actingAs($user)->get('/generations')->viewData('page')['version'];

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Inertia-Partial-Component' => 'generations/Index',
                'X-Inertia-Partial-Data' => 'generations,hasUnfinished',
                'X-Inertia-Infinite-Scroll-Merge-Intent' => 'prepend',
            ])
            ->get('/generations?page=1')
            ->assertOk()
            ->assertJsonPath('prependProps', ['generations.data'])
            ->assertJsonPath('matchPropsOn', ['generations.data.id']);
    }

    public function test_a_failed_generation_is_shown_as_costing_nothing(): void
    {
        $user = User::factory()->create();
        Generation::factory()->for($user)->failed()->create();

        $this->actingAs($user)
            ->get('/generations')
            ->assertInertia(fn (Assert $page) => $page
                ->where('generations.data.0.actual_charge', '$0.0000')
                ->where('hasUnfinished', false));
    }

    public function test_users_can_view_their_own_generation(): void
    {
        $generation = Generation::factory()->succeeded()->create();

        $this->actingAs($generation->user)
            ->get("/generations/{$generation->uuid}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('generations/Show')
                ->where('generation.id', $generation->uuid));
    }

    public function test_users_cannot_view_someone_elses_generation(): void
    {
        $generation = Generation::factory()->succeeded()->create();

        $this->actingAs(User::factory()->create())
            ->get("/generations/{$generation->uuid}")
            ->assertForbidden();
    }

    public function test_users_can_download_their_image_named_after_the_prompt(): void
    {
        Http::preventStrayRequests();
        $generation = Generation::factory()->succeeded()->create(['prompt' => 'A fox in the snow, watercolour']);
        Http::fake([$generation->image_url => Http::response('png-bytes', 200, ['Content-Type' => 'image/png'])]);

        $response = $this->actingAs($generation->user)->get("/generations/{$generation->uuid}/download");

        $response->assertOk()
            ->assertDownload('a-fox-in-the-snow-watercolour.png')
            ->assertHeader('Content-Type', 'image/png');
        $this->assertSame('png-bytes', $response->streamedContent());
    }

    public function test_users_cannot_download_someone_elses_image(): void
    {
        Http::preventStrayRequests();
        $generation = Generation::factory()->succeeded()->create();

        $this->actingAs(User::factory()->create())
            ->get("/generations/{$generation->uuid}/download")
            ->assertForbidden();
    }

    public function test_an_expired_image_cannot_be_downloaded(): void
    {
        Http::preventStrayRequests();
        $generation = Generation::factory()->succeeded()->create(['image_url_expires_at' => now()->subMinute()]);

        $this->actingAs($generation->user)
            ->get("/generations/{$generation->uuid}/download")
            ->assertRedirect("/generations/{$generation->uuid}")
            ->assertSessionHas('notify.type', 'error');
    }

    public function test_a_failed_fetch_from_pixelforge_sends_the_user_back_with_an_error(): void
    {
        Http::preventStrayRequests();
        $generation = Generation::factory()->succeeded()->create();
        Http::fake([$generation->image_url => Http::response('Forbidden', 403)]);

        $this->actingAs($generation->user)
            ->get("/generations/{$generation->uuid}/download")
            ->assertRedirect("/generations/{$generation->uuid}")
            ->assertSessionHas('notify.content', 'The image could not be fetched from Pixelforge. Please try again in a moment.');
    }

    public function test_submitting_holds_the_maximum_and_queues_the_generation(): void
    {
        Queue::fake();
        $this->fakePricing();
        $user = User::factory()->create();
        CreditPurchase::factory()->for($user)->create(['amount_nano' => 1_000_000_000]);

        $this->actingAs($user)
            ->post('/generations', [
                'prompt' => str_repeat('a', 400),
                'size' => '1536x1024',
                'quality' => 'high',
                'reference_images' => ['https://example.com/a.png', '', 'https://example.com/b.png'],
            ])
            ->assertRedirect('/generations');

        $generation = $user->generations()->sole();
        $this->assertSame(['https://example.com/a.png', 'https://example.com/b.png'], $generation->referenceImages->pluck('url')->all());
        $this->assertSame(467_406_250, $generation->ledgerEntries()->where('type', LedgerEntryType::Hold)->value('amount_nano'));
        Queue::assertPushed(DispatchWaitingGenerations::class);
    }

    public function test_someone_who_can_generate_but_not_see_history_stays_on_the_form(): void
    {
        Queue::fake();
        $this->fakePricing();
        $user = User::factory()->withoutRoles()->create()->givePermissionTo(PermissionName::GenerateImages);
        CreditPurchase::factory()->for($user)->create(['amount_nano' => 1_000_000_000]);

        $this->actingAs($user)
            ->post('/generations', ['prompt' => 'A lighthouse', 'size' => '1024x1024', 'quality' => 'low'])
            ->assertRedirect('/generations/create')
            ->assertSessionHas('notify.type', 'success');

        $this->assertSame(1, $user->generations()->count());
    }

    public function test_a_user_who_cannot_afford_it_is_told_and_nothing_is_created(): void
    {
        Queue::fake();
        $this->fakePricing();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/generations/create')
            ->post('/generations', ['prompt' => 'A lighthouse', 'size' => '1024x1024', 'quality' => 'low'])
            ->assertRedirect('/generations/create')
            ->assertSessionHasErrors('credits');

        $this->assertSame(0, Generation::count());
        Queue::assertNothingPushed();
    }

    public function test_nothing_is_created_or_charged_when_prices_cannot_be_loaded(): void
    {
        Queue::fake();
        Http::fake([self::PIXELFORGE_URL.'/pricing' => Http::failedConnection()]);
        $user = User::factory()->create();
        CreditPurchase::factory()->for($user)->create(['amount_nano' => 1_000_000_000]);

        $this->actingAs($user)
            ->from('/generations/create')
            ->post('/generations', ['prompt' => 'A lighthouse', 'size' => '1024x1024', 'quality' => 'low'])
            ->assertRedirect('/generations/create')
            ->assertSessionHasErrors('pricing');

        $this->assertSame(0, Generation::count());
    }

    public function test_reference_images_are_limited_to_four_http_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/generations', [
                'prompt' => 'A lighthouse',
                'size' => '1024x1024',
                'quality' => 'low',
                'reference_images' => ['ftp://example.com/a.png'],
            ])
            ->assertSessionHasErrors('reference_images.0');

        $this->actingAs($user)
            ->post('/generations', [
                'prompt' => 'A lighthouse',
                'size' => '1024x1024',
                'quality' => 'low',
                'reference_images' => array_map(fn (int $i): string => "https://example.com/{$i}.png", range(1, 5)),
            ])
            ->assertSessionHasErrors('reference_images');
    }

    public function test_the_live_quote_comes_from_the_backend(): void
    {
        $this->fakePricing();
        $user = User::factory()->create();
        CreditPurchase::factory()->for($user)->create(['amount_nano' => 100_000_000]);

        $this->actingAs($user)
            ->postJson('/generations/quote', [
                'prompt' => str_repeat('a', 400),
                'size' => '1536x1024',
                'quality' => 'high',
                'reference_image_count' => 2,
            ])
            ->assertOk()
            ->assertJson([
                'max_charge' => '$0.4673',
                'held_charge' => '$0.4674',
                'balance' => '$0.1000',
                'can_afford' => false,
            ]);
    }

    public function test_there_is_no_quote_without_a_prompt(): void
    {
        $this->fakePricing();

        $this->actingAs(User::factory()->create())
            ->postJson('/generations/quote', ['prompt' => '   ', 'size' => '1024x1024', 'quality' => 'medium', 'reference_image_count' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['prompt' => 'The prompt field is required.']);
    }
}
