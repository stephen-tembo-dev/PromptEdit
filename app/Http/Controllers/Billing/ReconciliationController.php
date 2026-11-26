<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\ReconciliationReportRequest;
use App\Http\Resources\Billing\ReconciliationReportResource;
use App\Services\Billing\ReconciliationService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin report page. Only users with the "view admin report" permission
 * can open it (see routes/web.php).
 */
class ReconciliationController extends Controller
{
    public function __construct(private ReconciliationService $reconciliation) {}

    /**
     * Build the report for the chosen dates and show the page.
     */
    public function __invoke(ReconciliationReportRequest $request): Response
    {
        $report = $this->reconciliation->report($request->startDate(), $request->endDate());

        return Inertia::render('billing/Reconciliation', [
            'report' => (new ReconciliationReportResource($report))->resolve(),
            'maxDays' => ReconciliationService::MAX_DAYS,
        ]);
    }
}
