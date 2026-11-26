<?php

namespace Tests\Feature\Jobs\Webhooks;

use App\Enums\Generations\GenerationStatus;
use App\Jobs\Generations\DispatchWaitingGenerations;
use App\Jobs\Webhooks\ProcessWebhookEvent;
use App\Models\Generations\Generation;
use App\Models\Webhooks\WebhookEvent;
use App\Services\Webhooks\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class ProcessWebhookEventTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_a_finished_generation_frees_its_slot_for_the_next_one(): void
    {
        $generation = Generation::factory()->processing()->create();
        $event = $this->storedEvent('generation.failed', $this->generationObject($generation, 'failed'));

        (new ProcessWebhookEvent($event))->handle(app(WebhookService::class));

        $this->assertSame(GenerationStatus::Failed, $generation->fresh()->status);
        $this->assertTrue($event->fresh()->isProcessed());
        Queue::assertPushed(DispatchWaitingGenerations::class);
    }

    public function test_an_event_that_frees_no_slot_dispatches_nothing(): void
    {
        $generation = Generation::factory()->queued()->create();
        $event = $this->storedEvent('generation.processing', $this->generationObject($generation, 'processing'));

        (new ProcessWebhookEvent($event))->handle(app(WebhookService::class));

        $this->assertSame(GenerationStatus::Processing, $generation->fresh()->status);
        Queue::assertNotPushed(DispatchWaitingGenerations::class);
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function storedEvent(string $type, array $object): WebhookEvent
    {
        $eventId = 'evt_'.$type;

        return WebhookEvent::factory()->create([
            'event_id' => $eventId,
            'payload' => json_encode([
                'id' => $eventId,
                'object' => 'event',
                'type' => $type,
                'created_at' => '2026-11-04T14:03:29Z',
                'data' => ['object' => $object],
            ], JSON_THROW_ON_ERROR),
        ]);
    }
}
