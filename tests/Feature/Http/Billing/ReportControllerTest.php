<?php

namespace Tests\Feature\Http\Billing;

use App\Models\Billing\CreditPurchase;
use App\Models\Billing\LedgerEntry;
use App\Models\Generations\Generation;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        CreditPurchase::factory()->for($this->user)->create(['amount_nano' => 10_000_000_000, 'created_at' => '2026-11-20 09:00:00']);

        // A finished image: held, then the hold returned and the real price charged.
        $finished = Generation::factory()->for($this->user)->succeeded()->create(['prompt' => 'A fox in the snow']);
        LedgerEntry::factory()->for($finished)->hold()->create(['amount_nano' => 120_000_000, 'created_at' => '2026-11-20 10:00:00']);
        LedgerEntry::factory()->for($finished)->release()->create(['amount_nano' => 120_000_000, 'created_at' => '2026-11-20 10:01:00']);
        LedgerEntry::factory()->for($finished)->charge()->create(['amount_nano' => 96_100_000, 'created_at' => '2026-11-20 10:01:00']);

        // Still being made, so its hold is still out.
        $running = Generation::factory()->for($this->user)->processing()->create();
        LedgerEntry::factory()->for($running)->hold()->create(['amount_nano' => 80_000_000, 'created_at' => '2026-11-20 11:00:00']);

        $someoneElses = Generation::factory()->succeeded()->create();
        LedgerEntry::factory()->for($someoneElses)->charge()->create(['amount_nano' => 500_000_000]);
        CreditPurchase::factory()->create();
    }

    public function test_customers_see_their_own_transactions_newest_first_with_totals_that_add_up(): void
    {
        $this->actingAs($this->user)
            ->get('/reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('billing/Reports')
                ->where('auth.can.viewReports', true)
                ->where('totals.bought', '$10.0000')
                ->where('totals.charged', '$0.0961')
                ->where('totals.held', '$0.0800')
                ->where('totals.balance', '$9.8239')
                ->where('filter', 'all')
                ->has('transactions.data', 5)
                ->where('transactions.data.0.kind', 'hold')
                ->where('transactions.data.0.amount', '−$0.0800')
                ->where('transactions.data.4.kind', 'purchase')
                ->where('transactions.data.4.amount', '+$10.0000')
                ->where('transactions.data.4.generation_id', null));
    }

    public function test_the_list_can_be_narrowed_to_one_kind(): void
    {
        $this->actingAs($this->user)
            ->get('/reports?type=charges')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filter', 'charges')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.label', 'Charged')
                ->where('transactions.data.0.prompt', 'A fox in the snow'));

        $this->actingAs($this->user)
            ->get('/reports?type=holds')
            ->assertInertia(fn (Assert $page) => $page->has('transactions.data', 3));
    }

    public function test_an_unknown_filter_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->get('/reports?type=everything')
            ->assertSessionHasErrors('type');
    }
}
