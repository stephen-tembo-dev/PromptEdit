<?php

namespace App\Services\User;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Models\User\User;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use App\Traits\PasswordValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

/**
 * Signing up and changing profile details. Fortify calls create() and update()
 * from its own routes, through the contracts this class implements. The users
 * page uses createWithAccess() and updateAccess().
 */
class UserService implements CreatesNewUsers, UpdatesUserProfileInformation
{
    use PasswordValidationRules;

    /**
     * Where people land after signing in: the first page on this list they're
     * allowed to open. Staff tools come first, so an admin lands on the admin
     * report even if they're also a customer.
     *
     * @var list<array{PermissionName, string}>
     */
    private const HOME_PAGES = [
        [PermissionName::ViewAdminReport, 'admin.report'],
        [PermissionName::ManageUsers, 'admin.users.index'],
        [PermissionName::ManageRoles, 'admin.roles.index'],
        [PermissionName::ViewDashboard, 'dashboard'],
        [PermissionName::GenerateImages, 'generations.create'],
        [PermissionName::ViewHistory, 'generations.index'],
        [PermissionName::ManageCredits, 'credits.index'],
        [PermissionName::ViewReports, 'reports.index'],
    ];

    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    /**
     * The URL of the user's home page, or null when no page is open to them.
     */
    public function homeUrl(User $user): ?string
    {
        foreach (self::HOME_PAGES as [$permission, $route]) {
            if ($user->can($permission->value)) {
                return route($route, absolute: false);
            }
        }

        return null;
    }

    /**
     * Check the sign-up form and save the new user as a Customer, which opens
     * the normal pages. Without a role they couldn't use the app at all.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = $this->users->create($input['name'], $input['email'], Hash::make($input['password']));

            $this->users->assignRole($user, RoleName::Customer);

            return $user;
        });
    }

    /**
     * Make an account from the users page, with its roles and permissions, in
     * one go so a failed role sync doesn't leave an account with no access.
     * The form has already been checked by StoreUserRequest.
     *
     * @param  list<int>  $roleIds
     * @param  list<PermissionName>  $permissions
     */
    public function createWithAccess(string $name, string $email, string $password, array $roleIds, array $permissions): User
    {
        return DB::transaction(function () use ($name, $email, $password, $roleIds, $permissions): User {
            $user = $this->users->create($name, $email, Hash::make($password));

            $this->users->syncAccess($user, $roleIds, $permissions);

            return $user;
        });
    }

    /**
     * Replace the roles and direct permissions of someone else's account.
     * UserPolicy stops people from changing their own.
     *
     * @param  list<int>  $roleIds
     * @param  list<PermissionName>  $permissions
     */
    public function updateAccess(User $user, array $roleIds, array $permissions): void
    {
        DB::transaction(fn () => $this->users->syncAccess($user, $roleIds, $permissions));
    }

    /**
     * Check the profile form and save the new name and email. If the email
     * changes on an account that must verify it, the new address has to be
     * verified again, so we send a fresh verification email.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
        ])->validateWithBag('updateProfileInformation');

        $mustReverifyEmail = $input['email'] !== $user->email && $user instanceof MustVerifyEmail;

        $this->users->updateProfile($user, $input['name'], $input['email'], resetEmailVerification: $mustReverifyEmail);

        if ($mustReverifyEmail) {
            $user->sendEmailVerificationNotification();
        }
    }
}
