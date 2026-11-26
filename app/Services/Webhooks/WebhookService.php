<?php

namespace App\Services\Webhooks;

use App\Enums\Webhooks\WebhookEventType;
use App\Models\Webhooks\WebhookEvent;
use App\Repositories\Contracts\Webhooks\WebhookEventRepositoryInterface;
use App\Services\Generations\ProviderUpdateService;
use App\Support\Data\Pixelforge\GenerationSnapshot;
use App\Support\Exceptions\Webhooks\InvalidWebhookSignatureException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;

/**
 * Handles messages Pixelforge sends us (webhooks), in three steps:
 *
 * 1. verifySignature() checks the message really came from Pixelforge.
 * 2. receive() saves it once. This has to be quick, because Pixelforge wants
 *    an answer within 10 seconds.
 * 3. process() does the real work later, in a queued job.
 */
class WebhookService
{
    public function __construct(
        private WebhookEventRepositoryInterface $events,
        private ProviderUpdateService $providerUpdates,
        #[Config('services.pixelforge.webhook_secret')] private ?string $secret,
        #[Config('services.pixelforge.webhook_tolerance_seconds')] private int $toleranceSeconds,
    ) {}

    /**
     * Check the Pixelforge-Signature header, which looks like "t=<time>,v1=<signature>".
     *
     * We build our own signature from the time and the raw body, using our
     * secret, and check it matches theirs. We also reject old messages, so
     * someone can't capture a real message and send it again later.
     *
     * @throws InvalidWebhookSignatureException
     */
    public function verifySignature(?string $header, string $payload): void
    {
        if ($this->secret === null || $this->secret === '') {
            throw new InvalidWebhookSignatureException('The Pixelforge webhook secret is not configured.');
        }

        [$timestamp, $signatures] = $this->parseSignatureHeader((string) $header);

        if ($timestamp === null || $signatures === []) {
            throw new InvalidWebhookSignatureException('The signature header is missing or malformed.');
        }

        if (abs(now()->getTimestamp() - (int) $timestamp) > $this->toleranceSeconds) {
            throw new InvalidWebhookSignatureException('The signature timestamp is outside the allowed window.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $this->secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return;
            }
        }

        throw new InvalidWebhookSignatureException('The signature does not match.');
    }

    /**
     * Save a message we've checked. Returns null if we've already saved this
     * event id, so a duplicate is accepted but never handled twice.
     *
     * @throws InvalidArgumentException|JsonException
     */
    public function receive(string $payload): ?WebhookEvent
    {
        $eventId = json_decode($payload, true, flags: JSON_THROW_ON_ERROR)['id'] ?? null;

        if (! is_string($eventId) || $eventId === '') {
            throw new InvalidArgumentException('The webhook payload has no event id.');
        }

        return $this->events->recordOnce($eventId, $payload);
    }

    /**
     * Apply a saved message to our picture, then mark the message as done.
     *
     * It's safe to run twice: the picture's status only moves forward and
     * the money is only settled once.
     */
    public function process(WebhookEvent $event): void
    {
        if ($event->isProcessed()) {
            return;
        }

        $envelope = $event->decodedPayload();
        $type = WebhookEventType::tryFrom((string) ($envelope['type'] ?? ''));

        DB::transaction(function () use ($event, $envelope, $type): void {
            if ($type?->carriesGeneration() && isset($envelope['data']['object'])) {
                $this->providerUpdates->apply(GenerationSnapshot::fromApi($envelope['data']['object']));
            }

            $this->events->markProcessed($event);
        });
    }

    /**
     * Split the signature header into the time and the signatures.
     * There can be more than one signature, for example while the secret is
     * being changed, so we keep them all.
     *
     * @return array{0: string|null, 1: list<string>}
     */
    private function parseSignatureHeader(string $header): array
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            if ($key === 't' && ctype_digit($value)) {
                $timestamp = $value;
            } elseif ($key === 'v1' && $value !== '') {
                $signatures[] = $value;
            }
        }

        return [$timestamp, $signatures];
    }
}
