<?php

namespace App\Services\Billing;

use App\Enums\Billing\LedgerEntryType;
use App\Enums\Generations\GenerationStatus;
use App\Models\Billing\CreditPurchase;
use App\Models\Generations\Generation;
use App\Models\User\User;
use App\Repositories\Contracts\Billing\CreditPurchaseRepositoryInterface;
use App\Repositories\Contracts\Billing\LedgerEntryRepositoryInterface;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use App\Support\Exceptions\Billing\InsufficientCreditsException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;

/**
 * The user's money: buying credits, taking deposits, charging and refunding
 * deposits.
 *
 * We never store the balance. We work it out every time from the credits
 * bought and the ledger. When a picture is requested we hold the most it
 * could cost. When it's done, we charge the real price (nothing if it failed)
 * and give the deposit back.
 */
class BalanceService
{
    public function __construct(
        private CreditPurchaseRepositoryInterface $purchases,
        private LedgerEntryRepositoryInterface $ledger,
        private UserRepositoryInterface $users,
        private PricingService $pricing,
    ) {}

    /**
     * How much the user can spend right now, with deposits already taken off.
     */
    public function balanceNano(User $user): int
    {
        return $this->purchases->totalForUser($user) + $this->ledger->netAmountForUser($user);
    }

    /**
     * Add credits to the user's account (a pretend purchase).
     * The same idempotency key only adds the credits once, so a double click is safe.
     */
    public function purchaseCredits(User $user, int $amountNano, string $idempotencyKey): CreditPurchase
    {
        if ($amountNano <= 0) {
            throw new InvalidArgumentException('A credit purchase must be a positive amount.');
        }

        return $this->purchases->recordOnce($user, $amountNano, $idempotencyKey);
    }

    /**
     * Lock the user's row and check they have enough money.
     *
     * Call this inside the same transaction that takes the deposit. A second
     * request then has to wait for this one, and sees the lower balance.
     *
     * @throws InsufficientCreditsException
     */
    public function lockAndEnsureCanAfford(User $user, int $amountNano): void
    {
        $this->users->lockForUpdate($user);

        $available = $this->balanceNano($user);

        if ($available < $amountNano) {
            throw new InsufficientCreditsException($amountNano, $available);
        }
    }

    /**
     * Take the deposit for a picture.
     */
    public function hold(Generation $generation, int $amountNano): void
    {
        $this->ledger->recordOnce($generation, LedgerEntryType::Hold, $amountNano);
    }

    /**
     * Settle the money once a picture is finished.
     *
     * If it succeeded, charge the real price. Either way, give the deposit
     * back. Each step can only be written once, so running this again (for
     * example after a duplicate webhook) changes nothing.
     */
    public function settle(Generation $generation): void
    {
        if (! $generation->isFinished()) {
            throw new LogicException("Generation {$generation->uuid} cannot be settled while {$generation->status->value}.");
        }

        $hold = $this->ledger->find($generation, LedgerEntryType::Hold);

        if ($generation->status === GenerationStatus::Succeeded) {
            $charge = $this->pricing->chargeNano($this->pricing->providerCostForGeneration($generation));
            $this->ledger->recordOnce($generation, LedgerEntryType::Charge, $charge);

            // This should never happen, because the deposit is the most a
            // picture can cost. If it does, we still charge the real price
            // and leave a warning so we can look into it.
            if ($hold !== null && $charge > $hold->amount_nano) {
                Log::warning('Actual charge exceeded the amount held.', [
                    'generation' => $generation->uuid,
                    'charge_nano' => $charge,
                    'hold_nano' => $hold->amount_nano,
                ]);
            }
        }

        if ($hold !== null) {
            $this->ledger->recordOnce($generation, LedgerEntryType::Release, $hold->amount_nano);
        }
    }
}
