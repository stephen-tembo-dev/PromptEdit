<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The password rules, shared by sign-up, password change and password reset,
 * so all three always ask for the same strength.
 */
trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }
}
