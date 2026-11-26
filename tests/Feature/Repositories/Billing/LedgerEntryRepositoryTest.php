<?php

namespace Tests\Feature\Repositories\Billing;

use App\Enums\Billing\LedgerEntryType;
use App\Models\Generations\Generation;
use App\Models\User\User;
use App\Repositories\Contracts\Billing\LedgerEntryRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerEntryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_the_same_entry_type_twice_keeps_only_the_first(): void
    {
        $ledger = app(LedgerEntryRepositoryInterface::class);
        $generation = Generation::factory()->succeeded()->create();

        $this->assertTrue($ledger->recordOnce($generation, LedgerEntryType::Charge, 60_000_000));
        $this->assertFalse($ledger->recordOnce($generation, LedgerEntryType::Charge, 99_000_000));

        $this->assertCount(1, $generation->ledgerEntries()->get());
        $this->assertSame(60_000_000, $ledger->find($generation, LedgerEntryType::Charge)?->amount_nano);
    }

    public function test_the_net_amount_covers_all_of_the_users_generations_and_nobody_elses(): void
    {
        $ledger = app(LedgerEntryRepositoryInterface::class);
        $user = User::factory()->create();

        $settled = Generation::factory()->for($user)->succeeded()->create();
        $ledger->recordOnce($settled, LedgerEntryType::Hold, 90_000_000);
        $ledger->recordOnce($settled, LedgerEntryType::Charge, 60_000_000);
        $ledger->recordOnce($settled, LedgerEntryType::Release, 90_000_000);

        $pending = Generation::factory()->for($user)->processing()->create();
        $ledger->recordOnce($pending, LedgerEntryType::Hold, 50_000_000);

        $someoneElses = Generation::factory()->processing()->create();
        $ledger->recordOnce($someoneElses, LedgerEntryType::Hold, 1_000_000_000);

        $this->assertSame(-110_000_000, $ledger->netAmountForUser($user));
        $this->assertSame(0, $ledger->netAmountForUser(User::factory()->create()));
    }
}
