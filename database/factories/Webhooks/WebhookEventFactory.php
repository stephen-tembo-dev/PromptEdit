<?php

namespace Database\Factories\Webhooks;

use App\Models\Webhooks\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    /**
     * Define the model's default state: an unprocessed generation.processing event.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventId = 'evt_'.Str::ulid();

        return [
            'event_id' => $eventId,
            'payload' => json_encode([
                'id' => $eventId,
                'object' => 'event',
                'type' => 'generation.processing',
                'created_at' => now()->toIso8601ZuluString(),
                'data' => [
                    'object' => [
                        'id' => 'gen_'.Str::lower(Str::random(24)),
                        'object' => 'generation',
                        'status' => 'processing',
                        'metadata' => ['generation_id' => (string) Str::uuid7()],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            'received_at' => now(),
        ];
    }

    public function processed(): static
    {
        return $this->state(['processed_at' => now()]);
    }
}
