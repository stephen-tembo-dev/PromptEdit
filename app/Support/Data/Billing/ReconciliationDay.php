<?php

namespace App\Support\Data\Billing;

use App\Enums\Billing\ReconciliationStatus;
use App\Support\Data\Pixelforge\UsageDay;

/**
 * One day (UTC) in the admin report: the pictures that finished that day,
 * what users paid for them, and what Pixelforge charged us.
 */
final readonly class ReconciliationDay
{
    public function __construct(
        public string $date,
        public int $generations,
        public TokenUsage $tokens,
        public int $providerCostNano,
        public int $chargedNano,
        public ?UsageDay $pixelforge,
        public ReconciliationStatus $status,
    ) {}

    /**
     * Pixelforge's cost minus ours. More than zero means Pixelforge charged us
     * for more than we have on record.
     */
    public function costDifferenceNano(): ?int
    {
        return $this->pixelforge === null ? null : $this->pixelforge->costNano - $this->providerCostNano;
    }

    /**
     * The profit for the day: what users paid minus what it cost us. We use
     * Pixelforge's cost when we have it.
     */
    public function marginNano(): int
    {
        return $this->chargedNano - ($this->pixelforge->costNano ?? $this->providerCostNano);
    }

    /**
     * True if anything happened that day, on our side or Pixelforge's.
     */
    public function hasActivity(): bool
    {
        return $this->generations > 0 || ($this->pixelforge?->generations ?? 0) > 0;
    }
}
