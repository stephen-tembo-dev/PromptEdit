<?php

namespace Tests\Feature\Jobs\Webhooks;

use App\Jobs\Webhooks\ProcessWebhookEvent;
use App\Jobs\Webhooks\RetryUnprocessedWebhookEvents;
use App\Models\Webhooks\WebhookEvent;
use App\Repositories\Contracts\Webhooks\WebhookEventRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetryUnprocessedWebhookEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_events_stuck_unprocessed_are_queued_again(): void
    {
        Queue::fake();
        $stuck = WebhookEvent::factory()->create(['received_at' => now()->subMinutes(10)]);
        WebhookEvent::factory()->create(['received_at' => now()->subMinute()]);
        WebhookEvent::factory()->processed()->create(['received_at' => now()->subHour()]);

        (new RetryUnprocessedWebhookEvents)->handle(app(WebhookEventRepositoryInterface::class));

        Queue::assertPushed(ProcessWebhookEvent::class, 1);
        Queue::assertPushed(ProcessWebhookEvent::class, fn (ProcessWebhookEvent $job): bool => $job->event->is($stuck));
    }
}
