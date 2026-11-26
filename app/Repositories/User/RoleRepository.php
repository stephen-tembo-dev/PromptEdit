<?php

namespace App\Repositories\User;

use App\Enums\User\PermissionName;
use App\Models\User\Role;
use App\Repositories\Contracts\User\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * The database queries for roles.
 */
class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Every role by name, with its permissions and user count.
     *
     * @return Collection<int, Role>
     */
    public function allWithUserCounts(): Collection
    {
        return $this->sorted(Role::query()->with('permissions')->withCount('users')->get());
    }

    /**
     * Every role by name, with its permissions.
     *
     * @return Collection<int, Role>
     */
    public function allWithPermissions(): Collection
    {
        return $this->sorted(Role::query()->with('permissions')->get());
    }

    /**
     * Save a new role with these permissions.
     *
     * @param  list<PermissionName>  $permissions
     */
    public function create(string $name, array $permissions): Role
    {
        $role = Role::create(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        return $role;
    }

    /**
     * Rename the role and replace its permissions.
     *
     * @param  list<PermissionName>  $permissions
     */
    public function update(Role $role, string $name, array $permissions): void
    {
        $role->update(['name' => $name]);
        $role->syncPermissions($permissions);
    }

    /**
     * Delete the role. The pivot rows go with it (cascading foreign keys).
     */
    public function delete(Role $role): void
    {
        $role->delete();
    }

    /**
     * Built-in roles first, then the rest A to Z. Sorted here rather than in
     * SQL because databases disagree on whether capitals come first.
     *
     * @param  Collection<int, Role>  $roles
     * @return Collection<int, Role>
     */
    private function sorted(Collection $roles): Collection
    {
        return $roles->sortBy(fn (Role $role): string => ($role->isBuiltIn() ? '0' : '1').mb_strtolower($role->label()))->values();
    }
}
