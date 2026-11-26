<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\User\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The site root. Guests see the welcome page. Signed-in users are sent to
 * their home page, which depends on their permissions (see UserService). It's
 * also where Fortify sends people after they sign in or sign up.
 */
class HomeController extends Controller
{
    public function __construct(private UserService $userService) {}

    /**
     * Show the welcome page or send the user home. Someone with no pages at all
     * gets the welcome page with a note, rather than a 403.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $home = $user === null ? null : $this->userService->homeUrl($user);

        if ($home !== null) {
            return redirect($home);
        }

        return Inertia::render('Welcome', [
            'appName' => config('app.name'),
            'hasNoAccess' => $user !== null,
        ]);
    }
}
