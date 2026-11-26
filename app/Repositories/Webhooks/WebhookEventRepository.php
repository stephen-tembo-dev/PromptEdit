<?php

namespace App\Repositories\Webhooks;

use App\Models\Webhooks\WebhookEvent;
use App\Repositories\Contracts\Webhooks\WebhookEventRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * The database queries for saved webhook messages.
 */
class WebhookEventRepository implements WebhookEventRepositoryInterface
{
    /**
     * Save the message unless its event id is already there. If two copies
     * arrive at the same moment, the database only lets one of them in.
     */
    public function recordOnce(string $eventId, string $payload): ?WebhookEvent
    {
        $inserted = WebhookEvent::query()->insertOrIgnore([
            'event_id' => $eventId,
            'payload' => $payload,
            'received_at' => now(),
        ]);

        if ($inserted === 0) {
            return null;
        }

        return WebhookEvent::query()->where('event_id', $eventId)->firstOrFail();
    }

    /**
     * Mark a message as handled.
     */
    public function markProcessed(WebhookEvent $event): void
    {
        $event->forceFill(['processed_at' => now()])->save();
    }

    /**
     * Messages saved before the cutoff that were never handled, oldest first.
     */
    public function unprocessedReceivedBefore(CarbonInterface $cutoff, int $limit): Collection
    {
        return WebhookEvent::query()
            ->whereNull('processed_at')
            ->where('received_at', '<', $cutoff)
            ->orderBy('received_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
