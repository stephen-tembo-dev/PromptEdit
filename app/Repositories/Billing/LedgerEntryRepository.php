<?php

namespace App\Repositories\Billing;

use App\Enums\Billing\LedgerEntryType;
use App\Models\Billing\LedgerEntry;
use App\Models\Generations\Generation;
use App\Models\User\User;
use App\Repositories\Contracts\Billing\LedgerEntryRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * The database queries for the money lines (holds, charges, releases).
 */
class LedgerEntryRepository implements LedgerEntryRepositoryInterface
{
    /**
     * Write a money line once per picture and type. We let the database's
     * unique rule stop duplicates, instead of checking first, because two
     * workers could both check at the same moment and both see nothing there.
     */
    public function recordOnce(Generation $generation, LedgerEntryType $type, int $amountNano): bool
    {
        $entry = $generation->ledgerEntries()->createOrFirst(
            ['type' => $type],
            ['amount_nano' => $amountNano],
        );

        return $entry->wasRecentlyCreated;
    }

    /**
     * Get the picture's line of this type, if there is one.
     */
    public function find(Generation $generation, LedgerEntryType $type): ?LedgerEntry
    {
        return $generation->ledgerEntries()->where('type', $type)->first();
    }

    /**
     * Add up the user's money lines. The database totals each type, then we
     * add or take away each total using the sign from LedgerEntryType.
     */
    public function netAmountForUser(User $user): int
    {
        return LedgerEntry::query()
            ->whereIn('generation_id', $user->generations()->select('id'))
            ->groupBy('type')
            ->selectRaw('type, SUM(amount_nano) AS total_nano')
            ->toBase()
            ->get()
            ->sum(fn (object $row): int => LedgerEntryType::from($row->type)->sign() * (int) $row->total_nano);
    }

    /**
     * Add up the user's charge lines, optionally only the recent ones.
     */
    public function chargedForUser(User $user, ?CarbonImmutable $since = null): int
    {
        return (int) LedgerEntry::query()
            ->whereIn('generation_id', $user->generations()->select('id'))
            ->where('type', LedgerEntryType::Charge)
            ->when($since, fn ($query, CarbonImmutable $since) => $query->where('created_at', '>=', $since))
            ->sum('amount_nano');
    }
}
