<?php

namespace App\Services\User;

use App\Enums\User\PermissionName;
use App\Models\User\Role;
use App\Repositories\Contracts\User\RoleRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Making, changing and deleting roles from the roles page. Built-in roles
 * belong to the seeder, and RolePolicy keeps this page away from them.
 */
class RoleService
{
    public function __construct(
        private RoleRepositoryInterface $roles,
    ) {}

    /**
     * Save a new role with its permissions.
     *
     * @param  list<PermissionName>  $permissions
     */
    public function create(string $name, array $permissions): Role
    {
        return DB::transaction(fn (): Role => $this->roles->create($name, $permissions));
    }

    /**
     * Rename the role and replace its permissions. Everyone with the role gets
     * the new permissions straight away.
     *
     * @param  list<PermissionName>  $permissions
     */
    public function update(Role $role, string $name, array $permissions): void
    {
        DB::transaction(fn () => $this->roles->update($role, $name, $permissions));
    }

    /**
     * Delete the role.
     */
    public function delete(Role $role): void
    {
        $this->roles->delete($role);
    }
}
