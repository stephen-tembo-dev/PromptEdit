<?php

namespace App\Support\Data\Billing;

/**
 * One user's totals in the admin report: their successful pictures in the date range.
 */
final readonly class UserSpend
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
        public int $generations,
        public int $providerCostNano,
        public int $chargedNano,
    ) {}

    /**
     * The profit we made from this user.
     */
    public function marginNano(): int
    {
        return $this->chargedNano - $this->providerCostNano;
    }
}
