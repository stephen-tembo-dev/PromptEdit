<?php

namespace App\Jobs\Webhooks;

use App\Repositories\Contracts\Webhooks\WebhookEventRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs on a schedule to find webhook messages we saved but never handled.
 *
 * This can happen if the job crashed or ran out of tries. Pixelforge won't
 * send the message again, because we already told it "200 OK". Handling a
 * message twice is safe, so we can just queue it again.
 */
class RetryUnprocessedWebhookEvents implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    private const STUCK_AFTER_MINUTES = 5;

    private const BATCH_SIZE = 100;

    public int $tries = 1;

    public int $uniqueFor = 300;

    /**
     * Queue a job for each message that's been stuck for 5 minutes or more.
     */
    public function handle(WebhookEventRepositoryInterface $events): void
    {
        $stuck = $events->unprocessedReceivedBefore(now()->subMinutes(self::STUCK_AFTER_MINUTES), self::BATCH_SIZE);

        foreach ($stuck as $event) {
            ProcessWebhookEvent::dispatch($event);
        }
    }
}
