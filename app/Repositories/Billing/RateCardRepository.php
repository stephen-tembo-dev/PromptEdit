<?php

namespace App\Repositories\Billing;

use App\Models\Billing\RateCard;
use App\Repositories\Contracts\Billing\RateCardRepositoryInterface;

/**
 * The database queries for saved Pixelforge price lists.
 */
class RateCardRepository implements RateCardRepositoryInterface
{
    /**
     * Get the price list with exactly these prices, or save it if it's new.
     */
    public function findOrCreate(
        int $inputTextNanoPerMillion,
        int $inputImageNanoPerMillion,
        int $outputNanoPerMillion,
        int $reasoningNanoPerMillion,
    ): RateCard {
        return RateCard::query()->firstOrCreate([
            'input_text_nano_per_million' => $inputTextNanoPerMillion,
            'input_image_nano_per_million' => $inputImageNanoPerMillion,
            'output_nano_per_million' => $outputNanoPerMillion,
            'reasoning_nano_per_million' => $reasoningNanoPerMillion,
        ]);
    }
}
