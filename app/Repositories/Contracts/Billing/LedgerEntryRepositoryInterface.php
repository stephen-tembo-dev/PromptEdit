<?php

namespace App\Repositories\Contracts\Billing;

use App\Enums\Billing\LedgerEntryType;
use App\Models\Billing\LedgerEntry;
use App\Models\Generations\Generation;
use App\Models\User\User;
use Carbon\CarbonImmutable;

/**
 * The list of database jobs for the money lines (holds, charges, releases).
 */
interface LedgerEntryRepositoryInterface
{
    /**
     * Write a hold, charge or release for a picture, unless it already has one
     * of that type. Returns false if it was already there.
     */
    public function recordOnce(Generation $generation, LedgerEntryType $type, int $amountNano): bool;

    /**
     * Get the picture's line of this type, if there is one.
     */
    public function find(Generation $generation, LedgerEntryType $type): ?LedgerEntry;

    /**
     * How all the user's money lines change their balance: releases added,
     * holds and charges taken away. Always zero or less.
     */
    public function netAmountForUser(User $user): int;

    /**
     * What the user has been charged for finished pictures, in total or only
     * since $since. Holds don't count: they're given back.
     */
    public function chargedForUser(User $user, ?CarbonImmutable $since = null): int;
}
