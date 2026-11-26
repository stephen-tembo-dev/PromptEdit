<?php

namespace Tests\Feature\Services\Generations;

use App\Enums\Billing\LedgerEntryType;
use App\Enums\Generations\GenerationStatus;
use App\Events\Generations\GenerationFinished;
use App\Events\Generations\GenerationUpdated;
use App\Models\Billing\CreditPurchase;
use App\Models\Generations\Generation;
use App\Services\Billing\BalanceService;
use App\Services\Generations\ProviderUpdateService;
use App\Support\Data\Pixelforge\GenerationSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class ProviderUpdateServiceTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    /** 12 x 5,000 + 765 x 10,000 + 6,450 x 40,000 + 3,184 x 12,500 nanodollars, plus 25%. */
    private const DOCS_EXAMPLE_CHARGE_NANO = 381_887_500;

    public function test_a_webhook_that_beats_the_create_response_still_finds_its_generation(): void
    {
        $generation = Generation::factory()->waitingForSlot()->create(['submitted_at' => now()]);
        $snapshot = $this->snapshot($generation, 'processing');

        $updated = app(ProviderUpdateService::class)->apply($snapshot);

        $this->assertTrue($generation->is($updated));
        $this->assertSame($snapshot->providerId, $updated->provider_id);
        $this->assertSame(GenerationStatus::Processing, $updated->status);
        $this->assertNotNull($updated->rate_card_id);
    }

    public function test_a_late_processing_event_cannot_undo_a_success(): void
    {
        $generation = $this->heldGeneration();
        $apply = app(ProviderUpdateService::class);

        $apply->apply($this->snapshot($generation, 'succeeded'));
        $apply->apply($this->snapshot($generation, 'processing'));

        $generation->refresh();
        $this->assertSame(GenerationStatus::Succeeded, $generation->status);
        $this->assertSame(6_450, $generation->output_tokens);
    }

    public function test_a_success_delivered_twice_is_charged_once(): void
    {
        $generation = $this->heldGeneration();
        $apply = app(ProviderUpdateService::class);

        $apply->apply($this->snapshot($generation, 'succeeded'));
        $apply->apply($this->snapshot($generation, 'succeeded'));

        $this->assertSame(1, $generation->ledgerEntries()->where('type', LedgerEntryType::Charge)->count());
        $this->assertSame(
            1_000_000_000 - self::DOCS_EXAMPLE_CHARGE_NANO,
            app(BalanceService::class)->balanceNano($generation->user),
        );
    }

    public function test_a_final_state_never_changes(): void
    {
        $generation = $this->heldGeneration();
        $apply = app(ProviderUpdateService::class);

        $apply->apply($this->snapshot($generation, 'failed'));
        $apply->apply($this->snapshot($generation, 'succeeded'));

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('generation_failed', $generation->error_code);
        $this->assertSame(1_000_000_000, app(BalanceService::class)->balanceNano($generation->user));
    }

    public function test_a_snapshot_for_a_generation_we_do_not_know_is_ignored(): void
    {
        $stranger = Generation::factory()->make(['uuid' => (string) Str::uuid7()]);

        $this->assertNull(app(ProviderUpdateService::class)->apply($this->snapshot($stranger, 'succeeded')));
    }

    /**
     * A generation Pixelforge has accepted, with $1.00 bought and $0.45 held.
     */
    public function test_a_finished_generation_announces_its_free_slot_once(): void
    {
        Event::fake([GenerationFinished::class]);
        $generation = $this->heldGeneration();
        $apply = app(ProviderUpdateService::class);

        $apply->apply($this->snapshot($generation, 'processing'));
        $apply->apply($this->snapshot($generation, 'succeeded'));
        $apply->apply($this->snapshot($generation, 'succeeded'));

        Event::assertDispatchedTimes(GenerationFinished::class, 1);
    }

    public function test_each_status_change_is_broadcast_to_the_owner_once(): void
    {
        Event::fake([GenerationUpdated::class]);
        $generation = $this->heldGeneration();
        $apply = app(ProviderUpdateService::class);

        $apply->apply($this->snapshot($generation, 'processing'));
        $apply->apply($this->snapshot($generation, 'processing'));
        $apply->apply($this->snapshot($generation, 'succeeded'));

        Event::assertDispatchedTimes(GenerationUpdated::class, 2);
    }

    public function test_a_different_outcome_for_a_finished_generation_is_logged_but_not_applied(): void
    {
        Log::spy();
        $generation = Generation::factory()->failed()->create();

        app(ProviderUpdateService::class)->apply($this->snapshot($generation, 'succeeded'));

        $this->assertSame(GenerationStatus::Failed, $generation->fresh()->status);
        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context['generation'] === $generation->uuid
            && $context['our_status'] === 'failed'
            && $context['pixelforge_status'] === 'succeeded');
    }

    private function heldGeneration(): Generation
    {
        $generation = Generation::factory()->queued()->create(['rate_card_id' => null]);
        CreditPurchase::factory()->for($generation->user)->create(['amount_nano' => 1_000_000_000]);
        app(BalanceService::class)->hold($generation, 450_000_000);

        return $generation;
    }

    private function snapshot(Generation $generation, string $status): GenerationSnapshot
    {
        return GenerationSnapshot::fromApi($this->generationObject($generation, $status));
    }
}
