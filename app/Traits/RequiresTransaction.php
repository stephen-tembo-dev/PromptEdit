<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * A row lock only lasts until the transaction ends. Without a transaction, the
 * lock is gone as soon as the query finishes, so it protects nothing. This trait
 * throws an error in that case, so the mistake can't go unnoticed.
 */
trait RequiresTransaction
{
    /**
     * Throw an error if we're not inside a database transaction.
     */
    protected function ensureInTransaction(string $operation): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException("{$operation} must run inside a database transaction.");
        }
    }
}
