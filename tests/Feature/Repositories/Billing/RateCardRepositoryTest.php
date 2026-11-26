<?php

namespace Tests\Feature\Repositories\Billing;

use App\Models\Billing\RateCard;
use App\Repositories\Contracts\Billing\RateCardRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateCardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_identical_rates_share_one_card_and_new_rates_get_their_own(): void
    {
        $rateCards = app(RateCardRepositoryInterface::class);

        $first = $rateCards->findOrCreate(5_000_000_000, 10_000_000_000, 40_000_000_000, 12_500_000_000);
        $same = $rateCards->findOrCreate(5_000_000_000, 10_000_000_000, 40_000_000_000, 12_500_000_000);
        $repriced = $rateCards->findOrCreate(5_000_000_000, 10_000_000_000, 45_000_000_000, 12_500_000_000);

        $this->assertTrue($first->is($same));
        $this->assertFalse($first->is($repriced));
        $this->assertSame(2, RateCard::count());
    }
}
