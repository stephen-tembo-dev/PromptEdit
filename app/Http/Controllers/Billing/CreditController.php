<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PurchaseCreditsRequest;
use App\Http\Resources\Billing\CreditPurchaseResource;
use App\Repositories\Contracts\Billing\CreditPurchaseRepositoryInterface;
use App\Services\Billing\BalanceService;
use App\Support\Money\NanoDollars;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Buy credits page.
 */
class CreditController extends Controller
{
    private const RECENT_PURCHASES = 20;

    public function __construct(
        private BalanceService $balance,
        private CreditPurchaseRepositoryInterface $purchases,
    ) {}

    /**
     * Show the page with the amounts to choose from and the user's latest purchases.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('billing/Credits', [
            'presets' => array_map(fn (int $dollars): array => [
                'value' => $dollars,
                'label' => NanoDollars::format($dollars * NanoDollars::PER_DOLLAR, 2),
            ], PurchaseCreditsRequest::PRESET_DOLLARS),
            'purchases' => CreditPurchaseResource::collection(
                $this->purchases->latestForUser($request->user(), self::RECENT_PURCHASES),
            )->resolve(),
        ]);
    }

    /**
     * Add credits to the user's balance. This is pretend, so no payment is taken.
     * The idempotency key means a double-clicked form only adds the credits once.
     */
    public function store(PurchaseCreditsRequest $request): RedirectResponse
    {
        $dollars = (int) $request->validated('amount_dollars');

        $purchase = $this->balance->purchaseCredits(
            $request->user(),
            $dollars * NanoDollars::PER_DOLLAR,
            $request->validated('idempotency_key'),
        );

        return back()->with('notify', $purchase->wasRecentlyCreated
            ? ['type' => 'success', 'content' => NanoDollars::format($purchase->amount_nano, 2).' added to your balance.']
            : ['type' => 'info', 'content' => 'That purchase was already recorded, so nothing was added twice.']);
    }
}
