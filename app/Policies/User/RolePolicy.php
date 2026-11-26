<?php

namespace App\Policies\User;

use App\Enums\User\PermissionName;
use App\Models\User\Role;
use App\Models\User\User;

/**
 * Who can change which roles. Built-in roles are reset by the seeder on every
 * deploy, so editing them here would be undone anyway.
 */
class RolePolicy
{
    /**
     * Role managers can edit any role they made.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can(PermissionName::ManageRoles->value) && ! $role->isBuiltIn();
    }

    /**
     * Same rule as editing.
     */
    public function delete(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }
}
