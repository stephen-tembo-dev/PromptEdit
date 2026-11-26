<?php

namespace App\Http\Controllers\User;

use App\Enums\User\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SaveRoleRequest;
use App\Http\Resources\User\RoleResource;
use App\Models\User\Role;
use App\Repositories\Contracts\User\RoleRepositoryInterface;
use App\Services\User\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The roles pages. Only users with "manage roles" get here (see routes/web.php),
 * and built-in roles can be looked at but not changed (see RolePolicy).
 */
class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService,
        private RoleRepositoryInterface $roles,
    ) {}

    /**
     * Every role, with its permissions and how many users have it.
     */
    public function index(): Response
    {
        return Inertia::render('roles/Index', [
            'roles' => RoleResource::collection($this->roles->allWithUserCounts())->resolve(),
            'permissions' => PermissionName::options(),
        ]);
    }

    /**
     * The New role form.
     */
    public function create(): Response
    {
        return Inertia::render('roles/Create', [
            'permissions' => PermissionName::options(),
        ]);
    }

    /**
     * Save the new role.
     */
    public function store(SaveRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create($request->validated('name'), $request->permissionNames());

        return to_route('admin.roles.index')->with('notify', ['type' => 'success', 'content' => "The {$role->label()} role was created."]);
    }

    /**
     * The edit form for a role made on this page.
     */
    public function edit(Role $role): Response
    {
        Gate::authorize('update', $role);

        return Inertia::render('roles/Edit', [
            'role' => (new RoleResource($role->load('permissions')->loadCount('users')))->resolve(),
            'permissions' => PermissionName::options(),
        ]);
    }

    /**
     * Save the role's new name and permissions.
     */
    public function update(SaveRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated('name'), $request->permissionNames());

        return to_route('admin.roles.index')->with('notify', ['type' => 'success', 'content' => "The {$role->label()} role was saved."]);
    }

    /**
     * Delete the role. Users who had it keep their account and lose its permissions.
     */
    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        $this->roleService->delete($role);

        return to_route('admin.roles.index')->with('notify', ['type' => 'success', 'content' => "The {$role->label()} role was deleted."]);
    }
}
