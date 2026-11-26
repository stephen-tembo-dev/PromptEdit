<?php

namespace Tests\Feature;

use App\Models\Billing\CreditPurchase;
use App\Models\Billing\LedgerEntry;
use App\Models\Generations\Generation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class BillingRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_generation_cannot_be_charged_twice(): void
    {
        $generation = Generation::factory()->succeeded()->create();
        LedgerEntry::factory()->for($generation)->charge()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        LedgerEntry::factory()->for($generation)->charge()->create();
    }

    public function test_a_generation_can_have_one_hold_one_release_and_one_charge(): void
    {
        $generation = Generation::factory()->succeeded()->create();

        LedgerEntry::factory()->for($generation)->hold()->create(['amount_nano' => 90_000_000]);
        LedgerEntry::factory()->for($generation)->charge()->create(['amount_nano' => 60_000_000]);
        LedgerEntry::factory()->for($generation)->release()->create(['amount_nano' => 90_000_000]);

        $this->assertSame(-60_000_000, $generation->ledgerEntries->sum->signedAmountNano());
    }

    public function test_ledger_entries_cannot_be_updated(): void
    {
        $entry = LedgerEntry::factory()->create(['amount_nano' => 100]);

        $this->expectException(LogicException::class);

        $entry->update(['amount_nano' => 1]);
    }

    public function test_ledger_entries_cannot_be_deleted(): void
    {
        $entry = LedgerEntry::factory()->create();

        $this->expectException(LogicException::class);

        $entry->delete();
    }

    public function test_a_purchase_idempotency_key_can_only_be_used_once(): void
    {
        $purchase = CreditPurchase::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        CreditPurchase::factory()->for($purchase->user)->create(['idempotency_key' => $purchase->idempotency_key]);
    }
}
