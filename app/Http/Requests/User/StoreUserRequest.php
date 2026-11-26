<?php

namespace App\Http\Requests\User;

use App\Models\User\User;
use App\Traits\AccessValidationRules;
use App\Traits\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checks the New user form. The password follows the same rules as sign-up.
 */
class StoreUserRequest extends FormRequest
{
    use AccessValidationRules, PasswordValidationRules;

    /**
     * Access is already checked on the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The rules for the form fields.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            ...$this->roleRules(),
            ...$this->permissionRules(),
        ];
    }
}
