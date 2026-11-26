<?php

namespace App\Http\Requests\User;

use App\Enums\User\RoleName;
use App\Models\User\Role;
use App\Traits\AccessValidationRules;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checks the role form, for both a new role and an edited one.
 */
class SaveRoleRequest extends FormRequest
{
    use AccessValidationRules;

    /**
     * Who can make roles is checked on the route. Built-in roles can't be
     * edited (see RolePolicy).
     */
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role === null || $this->user()->can('update', $role);
    }

    /**
     * The rules for the form fields. Built-in names are blocked in any letter
     * case, so nobody makes an "Admin" that looks like the real one.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Role::class)->where('guard_name', 'web')->ignore($this->route('role')),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (RoleName::tryFrom(mb_strtolower((string) $value)) !== null) {
                        $fail('This name belongs to a built-in role.');
                    }
                },
            ],
            ...$this->permissionRules(),
        ];
    }

    /**
     * Friendlier wording than "has already been taken" for a role name.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'There is already a role with this name.',
        ];
    }
}
