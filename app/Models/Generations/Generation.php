<?php

namespace App\Models\Generations;

use App\Enums\Generations\GenerationStatus;
use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Models\Billing\LedgerEntry;
use App\Models\Billing\RateCard;
use App\Models\User\User;
use App\Policies\Generations\GenerationPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\Generations\GenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One picture request from one user.
 *
 * The uuid is our own id. We send it to Pixelforge as the Idempotency-Key and
 * in the metadata, so retries are safe and webhooks can find the picture even
 * before we've saved Pixelforge's id (provider_id).
 *
 * Only the prompt, size and quality can be filled in from a form. Status,
 * tokens and money are only set by the services.
 *
 * The list below is just for the code editor, so it knows each column's type.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $rate_card_id
 * @property string|null $provider_id
 * @property string $prompt
 * @property ImageSize $size
 * @property ImageQuality $quality
 * @property GenerationStatus $status
 * @property int $submit_attempts
 * @property int $overload_attempts
 * @property bool $may_exist_at_provider
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $last_submit_attempt_at
 * @property int $estimated_charge_nano
 * @property int|null $input_text_tokens
 * @property int|null $input_image_tokens
 * @property int|null $output_tokens
 * @property int|null $reasoning_tokens
 * @property string|null $image_url
 * @property CarbonImmutable|null $image_url_expires_at
 * @property string|null $error_code
 * @property string|null $error_message
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 */
#[Fillable(['prompt', 'size', 'quality'])]
#[RouteKey('uuid')]
#[UsePolicy(GenerationPolicy::class)]
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'queued',
        'submit_attempts' => 0,
        'overload_attempts' => 0,
        'may_exist_at_provider' => false,
    ];

    /**
     * Laravel fills the uuid column automatically when the picture is created.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * The user who asked for the picture.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The prices Pixelforge fixed for this picture when it was created.
     *
     * @return BelongsTo<RateCard, $this>
     */
    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }

    /**
     * The reference image links, in the order the user gave them.
     *
     * @return HasMany<GenerationReferenceImage, $this>
     */
    public function referenceImages(): HasMany
    {
        return $this->hasMany(GenerationReferenceImage::class)->orderBy('position');
    }

    /**
     * The picture's money lines: hold, charge and release.
     *
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * True if the picture is still waiting on our side for a free slot.
     */
    public function isWaitingForSlot(): bool
    {
        return $this->status === GenerationStatus::Queued && $this->submitted_at === null;
    }

    /**
     * True if the picture has succeeded or failed.
     */
    public function isFinished(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => ImageSize::class,
            'quality' => ImageQuality::class,
            'status' => GenerationStatus::class,
            'submit_attempts' => 'integer',
            'overload_attempts' => 'integer',
            'may_exist_at_provider' => 'boolean',
            'estimated_charge_nano' => 'integer',
            'input_text_tokens' => 'integer',
            'input_image_tokens' => 'integer',
            'output_tokens' => 'integer',
            'reasoning_tokens' => 'integer',
            'submitted_at' => 'immutable_datetime',
            'last_submit_attempt_at' => 'immutable_datetime',
            'image_url_expires_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
