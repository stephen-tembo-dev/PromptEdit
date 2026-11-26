<?php

namespace Tests\Feature\Http\Webhooks;

use App\Jobs\Webhooks\ProcessWebhookEvent;
use App\Models\Webhooks\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PixelforgeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.pixelforge.webhook_secret' => self::SECRET]);
        Queue::fake();
    }

    public function test_a_signed_delivery_is_stored_and_queued_without_a_csrf_token(): void
    {
        $this->deliver($this->payload('evt_1'))->assertOk();

        $this->assertSame(1, WebhookEvent::count());
        Queue::assertPushed(ProcessWebhookEvent::class, 1);
    }

    public function test_a_delivery_does_not_start_a_session(): void
    {
        $this->deliver($this->payload('evt_1'))
            ->assertOk()
            ->assertCookieMissing(config('session.cookie'));
    }

    public function test_a_delivery_sent_twice_is_acknowledged_but_queued_once(): void
    {
        $payload = $this->payload('evt_1');

        $this->deliver($payload)->assertOk();
        $this->deliver($payload)->assertOk();

        $this->assertSame(1, WebhookEvent::count());
        Queue::assertPushed(ProcessWebhookEvent::class, 1);
    }

    public function test_a_bad_signature_is_rejected_and_nothing_is_stored(): void
    {
        $this->deliver($this->payload('evt_1'), secret: 'whsec_wrong')->assertStatus(400);

        $this->assertSame(0, WebhookEvent::count());
        Queue::assertNothingPushed();
    }

    public function test_a_stale_signature_is_rejected(): void
    {
        $this->deliver($this->payload('evt_1'), timestamp: now()->subMinutes(10)->getTimestamp())->assertStatus(400);

        $this->assertSame(0, WebhookEvent::count());
    }

    private function payload(string $eventId): string
    {
        return json_encode(['id' => $eventId, 'object' => 'event', 'type' => 'ping', 'created_at' => '2026-11-04T14:03:29Z', 'data' => []], JSON_THROW_ON_ERROR);
    }

    /**
     * Post the exact raw body, signed the way Pixelforge signs it.
     */
    private function deliver(string $payload, string $secret = self::SECRET, ?int $timestamp = null): TestResponse
    {
        $timestamp ??= now()->getTimestamp();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return $this->call('POST', '/webhooks/pixelforge', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PIXELFORGE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], content: $payload);
    }
}
