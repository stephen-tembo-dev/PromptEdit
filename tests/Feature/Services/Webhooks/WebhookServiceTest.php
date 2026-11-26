<?php

namespace Tests\Feature\Services\Webhooks;

use App\Enums\Billing\LedgerEntryType;
use App\Enums\Generations\GenerationStatus;
use App\Models\Billing\CreditPurchase;
use App\Models\Generations\Generation;
use App\Models\Webhooks\WebhookEvent;
use App\Services\Billing\BalanceService;
use App\Services\Webhooks\WebhookService;
use App\Support\Exceptions\Webhooks\InvalidWebhookSignatureException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class WebhookServiceTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    private const PAYLOAD = '{"id":"evt_1","type":"ping"}';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.pixelforge.webhook_secret' => self::SECRET]);
    }

    // verifySignature()

    public function test_a_correctly_signed_recent_delivery_is_accepted(): void
    {
        app(WebhookService::class)->verifySignature($this->signatureHeader(now()->getTimestamp()), self::PAYLOAD);

        $this->addToAssertionCount(1);
    }

    public function test_a_header_with_several_signatures_is_accepted_if_one_matches(): void
    {
        $timestamp = now()->getTimestamp();
        $valid = hash_hmac('sha256', $timestamp.'.'.self::PAYLOAD, self::SECRET);

        app(WebhookService::class)->verifySignature("t={$timestamp},v1=deadbeef,v1={$valid}", self::PAYLOAD);

        $this->addToAssertionCount(1);
    }

    public function test_a_body_changed_after_signing_is_rejected(): void
    {
        $this->expectException(InvalidWebhookSignatureException::class);

        app(WebhookService::class)->verifySignature(
            $this->signatureHeader(now()->getTimestamp()),
            str_replace('ping', 'generation.succeeded', self::PAYLOAD),
        );
    }

    public function test_a_signature_made_with_another_secret_is_rejected(): void
    {
        $this->expectException(InvalidWebhookSignatureException::class);

        app(WebhookService::class)->verifySignature($this->signatureHeader(now()->getTimestamp(), 'whsec_other'), self::PAYLOAD);
    }

    public function test_a_stale_timestamp_is_rejected_even_when_correctly_signed(): void
    {
        $this->expectException(InvalidWebhookSignatureException::class);

        app(WebhookService::class)->verifySignature($this->signatureHeader(now()->subSeconds(301)->getTimestamp()), self::PAYLOAD);
    }

    public function test_a_missing_or_malformed_header_is_rejected(): void
    {
        $service = app(WebhookService::class);

        foreach ([null, '', 'v1=abc', 't=123', 't=abc,v1=def'] as $header) {
            try {
                $service->verifySignature($header, self::PAYLOAD);
                $this->fail('Accepted header: '.var_export($header, true));
            } catch (InvalidWebhookSignatureException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    // receive() and process()

    public function test_a_delivery_sent_twice_is_stored_once(): void
    {
        $service = app(WebhookService::class);
        $payload = $this->envelope('evt_1', 'ping', null);

        $this->assertNotNull($service->receive($payload));
        $this->assertNull($service->receive($payload));
        $this->assertSame(1, WebhookEvent::count());
    }

    public function test_a_succeeded_event_completes_the_generation_and_charges_once(): void
    {
        $generation = Generation::factory()->processing()->create();
        CreditPurchase::factory()->for($generation->user)->create(['amount_nano' => 1_000_000_000]);
        app(BalanceService::class)->hold($generation, 450_000_000);
        $service = app(WebhookService::class);

        // [duplicate] sends every event twice; a different event id with the same
        // final snapshot must not charge again either.
        $first = $service->receive($this->envelope('evt_a', 'generation.succeeded', $this->generationObject($generation, 'succeeded')));
        $second = $service->receive($this->envelope('evt_b', 'generation.succeeded', $this->generationObject($generation, 'succeeded')));
        $service->process($first);
        $service->process($second);

        $this->assertSame(GenerationStatus::Succeeded, $generation->fresh()->status);
        $this->assertSame(1, $generation->ledgerEntries()->where('type', LedgerEntryType::Charge)->count());
        $this->assertTrue($first->fresh()->isProcessed());
        $this->assertTrue($second->fresh()->isProcessed());
    }

    public function test_a_ping_is_acknowledged_without_touching_any_generation(): void
    {
        $service = app(WebhookService::class);
        $event = $service->receive($this->envelope('evt_ping', 'ping', null));

        $service->process($event);

        $this->assertTrue($event->fresh()->isProcessed());
    }

    private function signatureHeader(int $timestamp, string $secret = self::SECRET): string
    {
        return "t={$timestamp},v1=".hash_hmac('sha256', $timestamp.'.'.self::PAYLOAD, $secret);
    }

    /**
     * @param  array<string, mixed>|null  $object
     */
    private function envelope(string $eventId, string $type, ?array $object): string
    {
        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => $type,
            'created_at' => '2026-11-04T14:03:29Z',
            'data' => $object === null ? [] : ['object' => $object],
        ], JSON_THROW_ON_ERROR);
    }
}
