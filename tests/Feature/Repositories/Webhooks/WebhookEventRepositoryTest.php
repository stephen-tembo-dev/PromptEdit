<?php

namespace Tests\Feature\Repositories\Webhooks;

use App\Models\Webhooks\WebhookEvent;
use App\Repositories\Contracts\Webhooks\WebhookEventRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookEventRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_duplicate_delivery_is_stored_only_once(): void
    {
        $events = app(WebhookEventRepositoryInterface::class);
        $payload = '{"id":"evt_1","type":"generation.succeeded"}';

        $stored = $events->recordOnce('evt_1', $payload);
        $duplicate = $events->recordOnce('evt_1', $payload);

        $this->assertNotNull($stored);
        $this->assertFalse($stored->isProcessed());
        $this->assertNull($duplicate);
        $this->assertSame(1, WebhookEvent::count());
    }

    public function test_unprocessed_events_older_than_the_cutoff_are_returned_oldest_first(): void
    {
        $older = WebhookEvent::factory()->create(['received_at' => now()->subMinutes(10)]);
        $old = WebhookEvent::factory()->create(['received_at' => now()->subMinutes(5)]);
        WebhookEvent::factory()->create(['received_at' => now()]);
        WebhookEvent::factory()->processed()->create(['received_at' => now()->subHour()]);

        $events = app(WebhookEventRepositoryInterface::class);
        $stuck = $events->unprocessedReceivedBefore(now()->subMinute(), 10);

        $this->assertSame([$older->id, $old->id], $stuck->pluck('id')->all());

        $events->markProcessed($older);

        $this->assertSame([$old->id], $events->unprocessedReceivedBefore(now()->subMinute(), 10)->pluck('id')->all());
    }
}
