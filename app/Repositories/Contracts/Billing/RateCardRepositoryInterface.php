<?php

namespace App\Repositories\Contracts\Billing;

use App\Models\Billing\RateCard;

/**
 * The list of database jobs for saved Pixelforge price lists.
 */
interface RateCardRepositoryInterface
{
    /**
     * Get the saved price list with exactly these prices, or save it if it's new.
     * Two workers saving the same prices at once still end up with one row.
     */
    public function findOrCreate(
        int $inputTextNanoPerMillion,
        int $inputImageNanoPerMillion,
        int $outputNanoPerMillion,
        int $reasoningNanoPerMillion,
    ): RateCard;
}
