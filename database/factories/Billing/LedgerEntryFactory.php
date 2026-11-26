<?php

namespace Database\Factories\Billing;

use App\Enums\Billing\LedgerEntryType;
use App\Models\Billing\LedgerEntry;
use App\Models\Generations\Generation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'generation_id' => Generation::factory(),
            'type' => LedgerEntryType::Hold,
            'amount_nano' => 100_000_000,
        ];
    }

    public function hold(): static
    {
        return $this->state(['type' => LedgerEntryType::Hold]);
    }

    public function release(): static
    {
        return $this->state(['type' => LedgerEntryType::Release]);
    }

    public function charge(): static
    {
        return $this->state(['type' => LedgerEntryType::Charge]);
    }
}
