<?php

namespace Database\Factories\Billing;

use App\Models\Billing\RateCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateCard>
 */
class RateCardFactory extends Factory
{
    /**
     * Pixelforge's published rates ($5.00, $10.00, $40.00 and $12.50 per million
     * tokens) in nanodollars per million tokens.
     *
     * @var array{input_text_nano_per_million: int, input_image_nano_per_million: int, output_nano_per_million: int, reasoning_nano_per_million: int}
     */
    public const PIXELFORGE_RATES = [
        'input_text_nano_per_million' => 5_000_000_000,
        'input_image_nano_per_million' => 10_000_000_000,
        'output_nano_per_million' => 40_000_000_000,
        'reasoning_nano_per_million' => 12_500_000_000,
    ];

    /**
     * Define the model's default state.
     *
     * Rate cards are unique per price set, so creating the default card twice
     * fails; use RateCard::firstOrCreate(self::PIXELFORGE_RATES) to share it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return self::PIXELFORGE_RATES;
    }
}
