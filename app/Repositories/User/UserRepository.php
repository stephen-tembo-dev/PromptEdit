<?php

namespace App\Repositories\User;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Models\User\User;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use App\Traits\RequiresTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The database queries for users.
 */
class UserRepository implements UserRepositoryInterface
{
    use RequiresTransaction;

    /**
     * Save a new user.
     */
    public function create(string $name, string $email, string $hashedPassword): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
        ]);
    }

    /**
     * Change the user's name and email, and clear the verification date if asked.
     */
    public function updateProfile(User $user, string $name, string $email, bool $resetEmailVerification = false): void
    {
        $attributes = ['name' => $name, 'email' => $email];

        if ($resetEmailVerification) {
            $attributes['email_verified_at'] = null;
        }

        $user->forceFill($attributes)->save();
    }

    /**
     * Replace the user's password.
     */
    public function updatePassword(User $user, string $hashedPassword): void
    {
        $user->forceFill(['password' => $hashedPassword])->save();
    }

    /**
     * One page of users, newest first, with their roles.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateWithRoles(?string $search, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($search, fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                $query->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%");
            }))
            ->latest()
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Give the user a built-in role.
     */
    public function assignRole(User $user, RoleName $role): void
    {
        $user->assignRole($role);
    }

    /**
     * Replace the user's roles and direct permissions.
     *
     * @param  list<int>  $roleIds
     * @param  list<PermissionName>  $permissions
     */
    public function syncAccess(User $user, array $roleIds, array $permissions): void
    {
        $user->syncRoles($roleIds);
        $user->syncPermissions($permissions);
    }

    /**
     * Lock the user's row. Must be called inside a transaction.
     */
    public function lockForUpdate(User $user): User
    {
        $this->ensureInTransaction('Locking a user');

        return User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
    }
}
