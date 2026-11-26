<?php

namespace Database\Factories\Billing;

use App\Models\Billing\CreditPurchase;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CreditPurchase>
 */
class CreditPurchaseFactory extends Factory
{
    /**
     * Define the model's default state: a $10.00 top-up.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount_nano' => 10_000_000_000,
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
