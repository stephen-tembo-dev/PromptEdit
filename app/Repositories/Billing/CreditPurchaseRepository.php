<?php

namespace App\Repositories\Billing;

use App\Models\Billing\CreditPurchase;
use App\Models\User\User;
use App\Repositories\Contracts\Billing\CreditPurchaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
     * The user's latest purchases, newest first.
     */
    public function latestForUser(User $user, int $limit): Collection
    {
        return $user->creditPurchases()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
