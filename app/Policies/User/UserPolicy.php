<?php

namespace App\Policies\User;

use App\Enums\User\PermissionName;
use App\Models\User\User;

/**
 * Who can change whose access on the users page.
 */
class UserPolicy
{
    /**
     * User managers can change anyone's roles and permissions except their own,
     * so nobody locks themselves out by unticking the wrong box.
     */
    public function updateAccess(User $user, User $target): bool
    {
        return $user->can(PermissionName::ManageUsers->value) && ! $user->is($target);
    }
}
