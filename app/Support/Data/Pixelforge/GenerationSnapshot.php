<?php

namespace App\Support\Data\Pixelforge;

use App\Enums\Generations\GenerationStatus;
use App\Support\Data\Billing\TokenRates;
use App\Support\Data\Billing\TokenUsage;
use Carbon\CarbonImmutable;

/**
 * What Pixelforge told us about a picture at one moment. It can come from the
 * reply when we send a picture, from polling, or from a webhook. We handle all
 * three the same way.
 */
final readonly class GenerationSnapshot
{
    public function __construct(
        public string $providerId,
        public GenerationStatus $status,
        public ?string $generationUuid,
        public ?TokenRates $rates,
        public ?TokenUsage $usage,
        public ?string $imageUrl,
        public ?CarbonImmutable $imageUrlExpiresAt,
        public ?string $errorCode,
        public ?string $errorMessage,
        public ?CarbonImmutable $startedAt,
        public ?CarbonImmutable $completedAt,
    ) {}

    /**
     * Read a picture from Pixelforge's answer.
     *
     * @param  array<string, mixed>  $object
     */
    public static function fromApi(array $object): self
    {
        $metadata = $object['metadata'] ?? [];
        $error = $object['error'] ?? null;

        return new self(
            providerId: (string) $object['id'],
            status: GenerationStatus::from((string) $object['status']),
            generationUuid: isset($metadata['generation_id']) ? (string) $metadata['generation_id'] : null,
            rates: isset($object['rates']) ? TokenRates::fromDecimalStrings($object['rates']) : null,
            usage: isset($object['usage']) ? TokenUsage::fromApi($object['usage']) : null,
            imageUrl: $object['image_url'] ?? null,
            imageUrlExpiresAt: self::timestamp($object['image_url_expires_at'] ?? null),
            errorCode: is_array($error) ? ($error['code'] ?? null) : null,
            errorMessage: is_array($error) ? ($error['message'] ?? null) : null,
            startedAt: self::timestamp($object['started_at'] ?? null),
            completedAt: self::timestamp($object['completed_at'] ?? null),
        );
    }

    /**
     * Turn Pixelforge's date text into a date, or null if there isn't one.
     */
    private static function timestamp(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value)->utc();
    }
}
