<?php

namespace App\Support\Exceptions\Billing;

use App\Support\Money\NanoDollars;
use RuntimeException;

/**
 * Thrown when the user doesn't have enough money for the deposit.
 */
class InsufficientCreditsException extends RuntimeException
{
    public function __construct(
        public readonly int $requiredNano,
        public readonly int $availableNano,
    ) {
        parent::__construct(sprintf(
            'This generation can cost up to %s, but your balance is %s.',
            NanoDollars::format($requiredNano),
            NanoDollars::format($availableNano),
        ));
    }
}
