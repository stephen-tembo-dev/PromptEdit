<?php

namespace App\Http\Controllers\User;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserAccessRequest;
use App\Http\Resources\User\RoleResource;
use App\Http\Resources\User\UserResource;
use App\Models\User\Role;
use App\Models\User\User;
use App\Repositories\Contracts\User\RoleRepositoryInterface;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use App\Services\User\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The users pages: the list, making an account, and changing someone's
 * roles and permissions. Only users with "manage users" get here (see
 * routes/web.php).
 */
class UserController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private UserService $userService,
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {}

    /**
     * The list of users, newest first, with an optional search.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', '')) ?: null;

        return Inertia::render('users/Index', [
            'users' => UserResource::collection($this->users->paginateWithRoles($search, self::PER_PAGE)),
            'search' => $search ?? '',
        ]);
    }

    /**
     * The New user form. Customer starts ticked, the same as signing up.
     */
    public function create(): Response
    {
        $roles = $this->roles->allWithPermissions();

        return Inertia::render('users/Create', [
            ...$this->accessOptions($roles),
            'defaultRoleIds' => $roles->filter(fn (Role $role): bool => $role->builtIn() === RoleName::Customer)->values()->modelKeys(),
        ]);
    }

    /**
     * Make the account and give it the ticked roles and permissions.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->createWithAccess(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
            $request->roleIds(),
            $request->permissionNames(),
        );

        return to_route('admin.users.edit', $user)->with('notify', [
            'type' => 'success',
            'content' => "{$user->name}'s account is ready. They can sign in with the password you set.",
        ]);
    }

    /**
     * One user's page, with their roles and permissions.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('users/Edit', [
            'user' => (new UserResource($user->load(['roles', 'permissions'])))->resolve(),
            ...$this->accessOptions(),
        ]);
    }

    /**
     * Save the user's new roles and permissions.
     */
    public function update(UpdateUserAccessRequest $request, User $user): RedirectResponse
    {
        $this->userService->updateAccess($user, $request->roleIds(), $request->permissionNames());

        return back()->with('notify', ['type' => 'success', 'content' => "{$user->name}'s access was updated."]);
    }

    /**
     * The roles and permissions to show as checkboxes.
     *
     * @param  Collection<int, Role>|null  $roles
     * @return array<string, mixed>
     */
    private function accessOptions(?Collection $roles = null): array
    {
        return [
            'roles' => RoleResource::collection($roles ?? $this->roles->allWithPermissions())->resolve(),
            'permissions' => PermissionName::options(),
        ];
    }
}
