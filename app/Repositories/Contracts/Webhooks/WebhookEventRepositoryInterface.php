<?php

namespace App\Repositories\Contracts\Webhooks;

use App\Models\Webhooks\WebhookEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * The list of database jobs for saved webhook messages.
 */
interface WebhookEventRepositoryInterface
{
    /**
     * Save a message unless we already have one with the same event id.
     * Returns null for a duplicate, even if two copies arrive at the same moment.
     */
    public function recordOnce(string $eventId, string $payload): ?WebhookEvent;

    /**
     * Mark a message as handled.
     */
    public function markProcessed(WebhookEvent $event): void;

    /**
     * Messages we saved but never handled (for example, the job crashed),
     * oldest first.
     *
     * @return Collection<int, WebhookEvent>
     */
    public function unprocessedReceivedBefore(CarbonInterface $cutoff, int $limit): Collection;
}
