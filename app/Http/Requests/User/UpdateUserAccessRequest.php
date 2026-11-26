<?php

namespace App\Http\Requests\User;

use App\Traits\AccessValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Checks the Access form on a user's page.
 */
class UpdateUserAccessRequest extends FormRequest
{
    use AccessValidationRules;

    /**
     * Anyone with "manage users" except the user themselves (see UserPolicy).
     */
    public function authorize(): bool
    {
        return $this->user()->can('updateAccess', $this->route('user'));
    }

    /**
     * The rules for the form fields.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->roleRules(),
            ...$this->permissionRules(),
        ];
    }
}
