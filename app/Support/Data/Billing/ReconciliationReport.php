<?php

namespace App\Support\Data\Billing;

use App\Enums\Billing\ReconciliationStatus;
use Carbon\CarbonImmutable;

/**
 * The whole admin report for a range of days (UTC), oldest day first.
 */
final readonly class ReconciliationReport
{
    /**
     * @param  list<ReconciliationDay>  $days
     * @param  list<UserSpend>  $users  biggest spender first
     */
    public function __construct(
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public array $days,
        public array $users,
        public bool $pixelforgeAvailable,
    ) {}

    /**
     * What users paid in total.
     */
    public function chargedNano(): int
    {
        return array_sum(array_map(fn (ReconciliationDay $day): int => $day->chargedNano, $this->days));
    }

    /**
     * What our own records say Pixelforge cost us in total.
     */
    public function providerCostNano(): int
    {
        return array_sum(array_map(fn (ReconciliationDay $day): int => $day->providerCostNano, $this->days));
    }

    /**
     * What Pixelforge says it charged us in total, or null if we couldn't load it.
     */
    public function pixelforgeCostNano(): ?int
    {
        if (! $this->pixelforgeAvailable) {
            return null;
        }

        return array_sum(array_map(fn (ReconciliationDay $day): int => $day->pixelforge->costNano ?? 0, $this->days));
    }

    /**
     * The total profit.
     */
    public function marginNano(): int
    {
        return $this->chargedNano() - ($this->pixelforgeCostNano() ?? $this->providerCostNano());
    }

    /**
     * How many days don't match Pixelforge's numbers.
     */
    public function mismatchedDayCount(): int
    {
        return count(array_filter($this->days, fn (ReconciliationDay $day): bool => $day->status === ReconciliationStatus::Mismatched));
    }
}
