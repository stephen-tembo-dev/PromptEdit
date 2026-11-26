<?php

namespace App\Support\Data\Billing;

/**
 * The prices we show before a picture is sent: "Estimated $X, at most $Y".
 *
 * - The estimate uses the middle of the reasoning range.
 * - The maximum is the most Pixelforge says it can cost. We show it as "at most".
 * - The hold is the maximum plus a little extra. This is the deposit we take.
 *
 * See PricingService::quote() for how they're worked out.
 */
final readonly class Quote
{
    public function __construct(
        public int $estimatedProviderCostNano,
        public int $maxProviderCostNano,
        public int $estimatedChargeNano,
        public int $maxChargeNano,
        public int $holdChargeNano,
    ) {}
}
