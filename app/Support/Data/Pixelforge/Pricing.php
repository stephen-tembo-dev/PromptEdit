<?php

namespace App\Support\Data\Pixelforge;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Support\Data\Billing\TokenRates;
use UnexpectedValueException;

/**
 * Pixelforge's price list: the prices, plus the token rules we use for quotes.
 */
final readonly class Pricing
{
    /**
     * @param  array<string, array<string, int>>  $outputTokens  quality => size => tokens
     * @param  array<string, array{min: int, max: int}>  $reasoningTokens  quality => range
     */
    public function __construct(
        public TokenRates $rates,
        public int $charactersPerTextToken,
        public int $textTokenVarianceBasisPoints,
        public int $tokensPerReferenceImage,
        private array $outputTokens,
        private array $reasoningTokens,
    ) {}

    /**
     * Read Pixelforge's price list.
     *
     * text_token_variance comes as a decimal (0.15). We turn it into a whole
     * number of basis points (1500) straight away, so we only use whole
     * numbers after that.
     *
     * @param  array<string, mixed>  $body
     */
    public static function fromApi(array $body): self
    {
        $tokens = $body['tokens'];

        return new self(
            TokenRates::fromDecimalStrings($body['rates']),
            (int) $tokens['characters_per_text_token'],
            (int) round((float) $tokens['text_token_variance'] * 10_000),
            (int) $tokens['per_reference_image'],
            $tokens['output'],
            $tokens['reasoning'],
        );
    }

    /**
     * How many output tokens a picture of this size and quality uses.
     */
    public function outputTokens(ImageSize $size, ImageQuality $quality): int
    {
        return (int) ($this->outputTokens[$quality->value][$size->value]
            ?? throw new UnexpectedValueException("Pixelforge has no output tokens for {$quality->value} {$size->value}."));
    }

    /**
     * The fewest and most reasoning tokens a picture of this quality can use.
     *
     * @return array{min: int, max: int}
     */
    public function reasoningTokenRange(ImageQuality $quality): array
    {
        $range = $this->reasoningTokens[$quality->value]
            ?? throw new UnexpectedValueException("Pixelforge has no reasoning range for {$quality->value}.");

        return ['min' => (int) $range['min'], 'max' => (int) $range['max']];
    }
}
