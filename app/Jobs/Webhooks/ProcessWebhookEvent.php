<?php

namespace App\Jobs\Webhooks;

use App\Models\Webhooks\WebhookEvent;
use App\Services\Webhooks\WebhookService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Handles one saved webhook message in the background.
 *
 * If every try fails, the message stays marked as not handled, and
 * RetryUnprocessedWebhookEvents will queue it again later.
 */
class ProcessWebhookEvent implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 15, 30, 60];

    public int $uniqueFor = 600;

    public function __construct(public WebhookEvent $event) {}

    /**
     * Only one job per message can be on the queue at a time.
     */
    public function uniqueId(): string
    {
        return $this->event->event_id;
    }

    /**
     * Apply the message to its picture.
     */
    public function handle(WebhookService $webhooks): void
    {
        $webhooks->process($this->event);
    }
}
