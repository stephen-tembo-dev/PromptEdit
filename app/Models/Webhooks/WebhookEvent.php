<?php

namespace App\Models\Webhooks;

use Carbon\CarbonImmutable;
use Database\Factories\Webhooks\WebhookEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A webhook message from Pixelforge that passed the signature check.
 * We only ever save one per event id.
 *
 * @property int $id
 * @property string $event_id
 * @property string $payload
 * @property CarbonImmutable $received_at
 * @property CarbonImmutable|null $processed_at
 */
#[Fillable(['event_id', 'payload'])]
#[Table(timestamps: false)]
class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory;

    /**
     * The message body turned back into an array (id, type, created_at, data.object).
     *
     * @return array<string, mixed>
     */
    public function decodedPayload(): array
    {
        return json_decode($this->payload, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * True once we've handled the message.
     */
    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
        ];
    }
}
