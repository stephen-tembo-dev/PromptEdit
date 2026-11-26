<?php

namespace Tests\Feature\Services\Billing;

use App\Enums\Billing\LedgerEntryType;
use App\Models\Generations\Generation;
use App\Models\User\User;
use App\Services\Billing\BalanceService;
use App\Support\Exceptions\Billing\InsufficientCreditsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_buying_credits_raises_the_balance_once_per_key(): void
    {
        $balance = app(BalanceService::class);
        $user = User::factory()->create();
        $key = (string) Str::uuid();

        $balance->purchaseCredits($user, 5_000_000_000, $key);
        $balance->purchaseCredits($user, 5_000_000_000, $key);

        $this->assertSame(5_000_000_000, $balance->balanceNano($user));
    }

    public function test_a_purchase_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(BalanceService::class)->purchaseCredits(User::factory()->create(), 0, (string) Str::uuid());
    }

    public function test_a_user_who_cannot_cover_the_maximum_is_blocked(): void
    {
        $balance = app(BalanceService::class);
        $user = User::factory()->create();
        $balance->purchaseCredits($user, 100_000_000, (string) Str::uuid());

        $this->expectException(InsufficientCreditsException::class);

        DB::transaction(fn () => $balance->lockAndEnsureCanAfford($user, 100_000_001));
    }

    public function test_a_success_charges_the_actual_price_and_releases_the_hold(): void
    {
        $balance = app(BalanceService::class);
        $generation = Generation::factory()->succeeded()->create([
            'input_text_tokens' => 41,
            'input_image_tokens' => 0,
            'output_tokens' => 3300,
            'reasoning_tokens' => 2950,
        ]);
        $user = $generation->user;
        $balance->purchaseCredits($user, 1_000_000_000, (string) Str::uuid());
        $balance->hold($generation, 300_000_000);

        $this->assertSame(700_000_000, $balance->balanceNano($user));

        $balance->settle($generation);
        $balance->settle($generation);

        // Provider cost $0.16908, plus 25% = $0.21135.
        $this->assertSame(211_350_000, $generation->ledgerEntries()->where('type', LedgerEntryType::Charge)->value('amount_nano'));
        $this->assertSame(1_000_000_000 - 211_350_000, $balance->balanceNano($user));
    }

    public function test_a_failure_costs_nothing_and_releases_the_hold_in_full(): void
    {
        $balance = app(BalanceService::class);
        $generation = Generation::factory()->failed()->create();
        $user = $generation->user;
        $balance->purchaseCredits($user, 1_000_000_000, (string) Str::uuid());
        $balance->hold($generation, 300_000_000);

        $balance->settle($generation);

        $this->assertNull($generation->ledgerEntries()->where('type', LedgerEntryType::Charge)->first());
        $this->assertSame(1_000_000_000, $balance->balanceNano($user));
    }
}
