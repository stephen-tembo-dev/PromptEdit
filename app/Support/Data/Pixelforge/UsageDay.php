<?php

namespace App\Support\Data\Pixelforge;

use App\Support\Data\Billing\TokenUsage;
use App\Support\Money\NanoDollars;

/**
 * One day (UTC) from Pixelforge's usage report: what it charged our API key
 * for the pictures that finished that day. Failed pictures are never charged.
 */
final readonly class UsageDay
{
    public function __construct(
        public string $date,
        public int $generations,
        public TokenUsage $tokens,
        public int $costNano,
    ) {}

    /**
     * Read one day from Pixelforge's usage report.
     *
     * @param  array{date: string, generations: int, input_text_tokens: int, input_image_tokens: int, output_tokens: int, reasoning_tokens: int, cost_usd: string}  $day
     */
    public static function fromApi(array $day): self
    {
        return new self(
            date: $day['date'],
            generations: (int) $day['generations'],
            tokens: TokenUsage::fromApi($day),
            costNano: NanoDollars::fromDecimalString($day['cost_usd']),
        );
    }
}
