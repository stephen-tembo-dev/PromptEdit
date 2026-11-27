<?php

namespace App\Repositories\Billing;

use App\Models\Billing\CreditPurchase;
use App\Models\User\User;
use App\Repositories\Contracts\Billing\CreditPurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The database queries for credit purchases.
 */
class CreditPurchaseRepository implements CreditPurchaseRepositoryInterface
{
    /**
     * Save a purchase once per idempotency key. We only look at this user's
     * purchases, so someone else's key can never come back here. It just
     * fails instead.
     */
    public function recordOnce(User $user, int $amountNano, string $idempotencyKey): CreditPurchase
    {
        return $user->creditPurchases()->createOrFirst(
            ['idempotency_key' => $idempotencyKey],
            ['amount_nano' => $amountNano],
        );
    }

    /**
     * All the credits the user has bought, added up.
     */
    public function totalForUser(User $user): int
    {
        return (int) $user->creditPurchases()->sum('amount_nano');
    }

    /**
     * One page of the user's purchases, newest first.
     */
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $user->creditPurchases()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
