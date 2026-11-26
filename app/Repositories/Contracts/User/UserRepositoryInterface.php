<?php

namespace App\Repositories\Contracts\User;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The list of database jobs for users.
 */
interface UserRepositoryInterface
{
    /**
     * Save a new user. The password must already be hashed.
     */
    public function create(string $name, string $email, string $hashedPassword): User;

    /**
     * Change the user's name and email. When the email changes on an account
     * that has to verify it, pass $resetEmailVerification so the new address
     * counts as unverified.
     */
    public function updateProfile(User $user, string $name, string $email, bool $resetEmailVerification = false): void;

    /**
     * Replace the user's password. The password must already be hashed.
     */
    public function updatePassword(User $user, string $hashedPassword): void;

    /**
     * One page of users for the users page, newest first, with their roles.
     * $search matches part of the name or email.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateWithRoles(?string $search, int $perPage): LengthAwarePaginator;

    /**
     * Give the user one of the built-in roles, keeping any they already have.
     */
    public function assignRole(User $user, RoleName $role): void;

    /**
     * Replace the user's roles and the permissions given to them directly.
     *
     * @param  list<int>  $roleIds
     * @param  list<PermissionName>  $permissions
     */
    public function syncAccess(User $user, array $roleIds, array $permissions): void;

    /**
     * Lock the user's row until the transaction ends. We do this while checking
     * the balance, so two requests can't both pass the check with the same money.
     */
    public function lockForUpdate(User $user): User;
}
