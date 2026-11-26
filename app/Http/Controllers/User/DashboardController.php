<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Generations\GenerationResource;
use App\Models\Generations\Generation;
use App\Repositories\Contracts\Billing\LedgerEntryRepositoryInterface;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use App\Support\Money\NanoDollars;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's dashboard: how many images they've made, what they've spent,
 * what's still being made, and their latest images. The balance comes from the
 * shared props.
 */
class DashboardController extends Controller
{
    private const RECENT = 3;

    public function __construct(
        private GenerationRepositoryInterface $generations,
        private LedgerEntryRepositoryInterface $ledger,
    ) {}

    /**
     * Show the dashboard. It refreshes itself while anything is unfinished,
     * the same way the history page does.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $monthStart = CarbonImmutable::now()->startOfMonth();

        $counts = $this->generations->countsForUser($user, $monthStart);
        $spentNano = $this->ledger->chargedForUser($user);
        $recent = $this->generations->latestForUser($user, self::RECENT);

        return Inertia::render('Dashboard', [
            'stats' => [
                'images' => $counts['succeeded'],
                'images_this_month' => $counts['succeeded_since'],
                'spent' => NanoDollars::format($spentNano),
                'spent_this_month' => NanoDollars::format($this->ledger->chargedForUser($user, $monthStart)),
                // Only successful images are charged, so this is the true average.
                'average_cost' => $counts['succeeded'] > 0 ? NanoDollars::format(intdiv($spentNano, $counts['succeeded'])) : null,
                'unfinished' => $counts['unfinished'],
            ],
            'recent' => GenerationResource::collection($recent)->resolve(),
            'hasUnfinished' => $recent->contains(fn (Generation $generation): bool => ! $generation->isFinished()) || $counts['unfinished'] > 0,
        ]);
    }
}
