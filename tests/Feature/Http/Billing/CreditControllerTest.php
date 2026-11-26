<?php

namespace Tests\Feature\Http\Billing;

use App\Models\Billing\CreditPurchase;
use App\Models\User\User;
use App\Services\Billing\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreditControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_credits_page_shows_the_balance_presets_and_purchases(): void
    {
        $user = User::factory()->create();
        CreditPurchase::factory()->for($user)->create(['amount_nano' => 10_000_000_000]);

        $this->actingAs($user)
            ->get('/credits')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('billing/Credits')
                ->where('balance', '$10.0000')
                ->has('presets', 3)
                ->has('purchases', 1)
                ->where('purchases.0.amount', '$10.00'));
    }

    public function test_buying_credits_adds_them_once_per_idempotency_key(): void
    {
        $user = User::factory()->create();
        $purchase = ['amount_dollars' => 5, 'idempotency_key' => (string) Str::uuid()];

        $this->actingAs($user)->post('/credits', $purchase)->assertSessionHas('notify.type', 'success');
        $this->actingAs($user)->post('/credits', $purchase)->assertSessionHas('notify.type', 'info');

        $this->assertSame(5_000_000_000, app(BalanceService::class)->balanceNano($user));
    }

    public function test_only_the_offered_amounts_can_be_bought(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/credits', ['amount_dollars' => 1_000_000, 'idempotency_key' => (string) Str::uuid()])
            ->assertSessionHasErrors('amount_dollars');

        $this->assertSame(0, CreditPurchase::count());
    }
}
