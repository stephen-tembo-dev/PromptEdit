<?php

namespace App\Http\Middleware;

use App\Enums\User\PermissionName;
use App\Services\Billing\BalanceService;
use App\Support\Money\NanoDollars;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Sends the data every page needs (the user, their balance, messages) to the Vue pages.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(private BalanceService $balance) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only('id', 'name', 'email'),
                // Which menu links to show. The routes still check access themselves.
                'can' => [
                    'viewDashboard' => (bool) $request->user()?->can(PermissionName::ViewDashboard->value),
                    'generateImages' => (bool) $request->user()?->can(PermissionName::GenerateImages->value),
                    'viewHistory' => (bool) $request->user()?->can(PermissionName::ViewHistory->value),
                    'manageCredits' => (bool) $request->user()?->can(PermissionName::ManageCredits->value),
                    'viewReports' => (bool) $request->user()?->can(PermissionName::ViewReports->value),
                    'viewAdminReport' => (bool) $request->user()?->can(PermissionName::ViewAdminReport->value),
                    'manageUsers' => (bool) $request->user()?->can(PermissionName::ManageUsers->value),
                    'manageRoles' => (bool) $request->user()?->can(PermissionName::ManageRoles->value),
                ],
            ],
            // The user's balance, ready to show. It's only worked out when a page
            // asks for it, so the history page's refreshes don't recalculate it.
            'balance' => fn () => $request->user() === null
                ? null
                : NanoDollars::format($this->balance->balanceNano($request->user())),
            // A pop-up message after a redirect, e.g. ->with('notify', ['type' => 'success', 'content' => 'Saved']).
            'notify' => fn () => $request->session()->get('notify'),
        ];
    }
}
