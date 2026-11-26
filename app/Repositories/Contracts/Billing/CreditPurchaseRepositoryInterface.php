<?php

namespace App\Repositories\Contracts\Billing;

use App\Models\Billing\CreditPurchase;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The list of database jobs for credit purchases.
 */
interface CreditPurchaseRepositoryInterface
{
    /**
     * Save a credit purchase. If the same idempotency key comes again, we return
     * the first purchase instead of adding the credits twice.
     */
    public function recordOnce(User $user, int $amountNano, string $idempotencyKey): CreditPurchase;

    /**
     * All the credits the user has ever bought, added up.
     */
    public function totalForUser(User $user): int;

    /**
     * The user's latest purchases, newest first.
     *
     * @return Collection<int, CreditPurchase>
     */
    public function latestForUser(User $user, int $limit): Collection;
}
