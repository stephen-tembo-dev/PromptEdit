<?php

namespace App\Repositories\Contracts\User;

use App\Enums\User\PermissionName;
use App\Models\User\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * The list of database jobs for roles.
 */
interface RoleRepositoryInterface
{
    /**
     * Every role, built-in ones first, with its permissions and how many users have it.
     *
     * @return Collection<int, Role>
     */
    public function allWithUserCounts(): Collection;

    /**
     * Every role, built-in ones first, with its permissions. For the checkboxes on the user forms.
     *
     * @return Collection<int, Role>
     */
    public function allWithPermissions(): Collection;

    /**
     * Save a new role with these permissions.
     *
     * @param  list<PermissionName>  $permissions
     */
    public function create(string $name, array $permissions): Role;

    /**
     * Rename the role and replace its permissions.
     *
     * @param  list<PermissionName>  $permissions
     */
    public function update(Role $role, string $name, array $permissions): void;

    /**
     * Delete the role. Users who had it simply lose it.
     */
    public function delete(Role $role): void;
}
