<?php

namespace App\Support\Data\Billing;

use App\Models\Billing\RateCard;
use App\Support\Money\NanoDollars;

/**
 * Pixelforge's price for each kind of token, in nanodollars per million tokens.
 */
final readonly class TokenRates
{
    public function __construct(
        public int $inputTextNanoPerMillion,
        public int $inputImageNanoPerMillion,
        public int $outputNanoPerMillion,
        public int $reasoningNanoPerMillion,
    ) {}

    /**
     * Read Pixelforge's prices, which come as dollar amounts in text, per million tokens.
     *
     * @param  array{input_text_tokens: string, input_image_tokens: string, output_tokens: string, reasoning_tokens: string}  $rates
     */
    public static function fromDecimalStrings(array $rates): self
    {
        return new self(
            NanoDollars::fromDecimalString($rates['input_text_tokens']),
            NanoDollars::fromDecimalString($rates['input_image_tokens']),
            NanoDollars::fromDecimalString($rates['output_tokens']),
            NanoDollars::fromDecimalString($rates['reasoning_tokens']),
        );
    }

    /**
     * Read the prices saved on a rate card.
     */
    public static function fromRateCard(RateCard $rateCard): self
    {
        return new self(
            $rateCard->input_text_nano_per_million,
            $rateCard->input_image_nano_per_million,
            $rateCard->output_nano_per_million,
            $rateCard->reasoning_nano_per_million,
        );
    }
}
