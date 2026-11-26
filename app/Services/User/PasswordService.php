<?php

namespace App\Services\User;

use App\Models\User\User;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use App\Traits\PasswordValidationRules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

/**
 * Changing a password while signed in, and resetting a forgotten one. Fortify
 * calls these from its own routes, through the contracts this class implements.
 */
class PasswordService implements ResetsUserPasswords, UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    /**
     * Change the password of a signed-in user, who must type their current
     * password first.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $this->users->updatePassword($user, Hash::make($input['password']));
    }

    /**
     * Set a new password from a reset link. Fortify has already checked the
     * link's token before calling this.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $this->users->updatePassword($user, Hash::make($input['password']));
    }
}
