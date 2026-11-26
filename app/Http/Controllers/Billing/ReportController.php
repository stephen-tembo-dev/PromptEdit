<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\TransactionReportRequest;
use App\Http\Resources\Billing\TransactionResource;
use App\Repositories\Contracts\Billing\CreditPurchaseRepositoryInterface;
use App\Repositories\Contracts\Billing\LedgerEntryRepositoryInterface;
use App\Repositories\Contracts\Billing\TransactionRepositoryInterface;
use App\Services\Billing\BalanceService;
use App\Support\Money\NanoDollars;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's Reports page: every money movement on their account, and
 * totals that show how they add up to the balance.
 */
class ReportController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(
        private TransactionRepositoryInterface $transactions,
        private CreditPurchaseRepositoryInterface $purchases,
        private LedgerEntryRepositoryInterface $ledger,
        private BalanceService $balance,
    ) {}

    /**
     * Show the totals and one page of transactions.
     */
    public function index(TransactionReportRequest $request): Response
    {
        $user = $request->user();

        $boughtNano = $this->purchases->totalForUser($user);
        $chargedNano = $this->ledger->chargedForUser($user);
        $balanceNano = $this->balance->balanceNano($user);

        return Inertia::render('billing/Reports', [
            'totals' => [
                'bought' => NanoDollars::format($boughtNano),
                'charged' => NanoDollars::format($chargedNano),
                // Whatever isn't charged or still spendable is held for images being made.
                'held' => NanoDollars::format($boughtNano - $chargedNano - $balanceNano),
                'balance' => NanoDollars::format($balanceNano),
            ],
            'transactions' => TransactionResource::collection($this->transactions->paginateForUser($user, $request->kinds(), self::PER_PAGE)),
            'filter' => $request->filter() ?? 'all',
        ]);
    }
}
