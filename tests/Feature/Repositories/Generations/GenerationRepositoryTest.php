<?php

namespace Tests\Feature\Repositories\Generations;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Models\Generations\Generation;
use App\Models\User\User;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_generation_waits_for_a_slot_and_keeps_its_reference_images_in_order(): void
    {
        $generation = app(GenerationRepositoryInterface::class)->create(
            User::factory()->create(),
            'A lighthouse on a cliff at dawn',
            ImageSize::Landscape,
            ImageQuality::High,
            ['https://example.com/a.png', 'https://example.com/b.png'],
            470_000_000,
        );

        $generation->refresh();

        $this->assertTrue($generation->isWaitingForSlot());
        $this->assertSame(470_000_000, $generation->estimated_charge_nano);
        $this->assertSame(ImageSize::Landscape, $generation->size);
        $this->assertSame(
            ['https://example.com/a.png', 'https://example.com/b.png'],
            $generation->referenceImages->pluck('url')->all(),
        );
    }

    public function test_generations_waiting_for_a_slot_are_returned_oldest_first(): void
    {
        $newest = Generation::factory()->waitingForSlot()->create(['created_at' => now()->subMinute()]);
        $oldest = Generation::factory()->waitingForSlot()->create(['created_at' => now()->subMinutes(3)]);
        Generation::factory()->waitingForSlot()->create(['created_at' => now()]);
        Generation::factory()->queued()->create(['created_at' => now()->subHour()]);

        $waiting = app(GenerationRepositoryInterface::class)->waitingForSlot(2);

        $this->assertSame([$oldest->id, $newest->id], $waiting->pluck('id')->all());
    }

    public function test_only_submitted_unfinished_generations_hold_a_slot(): void
    {
        Generation::factory()->waitingForSlot()->create();
        $queued = Generation::factory()->queued()->create();
        $processing = Generation::factory()->processing()->create();
        Generation::factory()->succeeded()->create();
        Generation::factory()->failed()->create();

        $generations = app(GenerationRepositoryInterface::class);

        $this->assertEqualsCanonicalizing([$queued->id, $processing->id], $generations->inFlight()->pluck('id')->all());
        $this->assertSame(2, $generations->countInFlight());
    }

    public function test_history_shows_only_the_users_own_generations_newest_first(): void
    {
        $user = User::factory()->create();
        $older = Generation::factory()->for($user)->succeeded()->create(['created_at' => now()->subDay()]);
        $newer = Generation::factory()->for($user)->processing()->create(['created_at' => now()]);
        Generation::factory()->succeeded()->create();

        $page = app(GenerationRepositoryInterface::class)->paginateForUser($user);

        $this->assertSame([$newer->id, $older->id], collect($page->items())->pluck('id')->all());
    }
}
