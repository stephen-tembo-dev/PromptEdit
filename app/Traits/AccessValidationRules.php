<?php

namespace App\Traits;

use App\Enums\User\PermissionName;
use App\Models\User\Role;
use Illuminate\Validation\Rule;

/**
 * The roles and permissions checkboxes, shared by the user and role forms so
 * they accept the same values and hand them over the same way.
 */
trait AccessValidationRules
{
    /**
     * Rules for the "roles" checkboxes (role ids).
     *
     * @return array<string, mixed>
     */
    protected function roleRules(): array
    {
        return [
            'roles' => ['present', 'array'],
            'roles.*' => ['integer', 'distinct', Rule::exists(Role::class, 'id')],
        ];
    }

    /**
     * Rules for the "permissions" checkboxes (PermissionName values).
     *
     * @return array<string, mixed>
     */
    protected function permissionRules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::enum(PermissionName::class)],
        ];
    }

    /**
     * The ticked role ids.
     *
     * @return list<int>
     */
    public function roleIds(): array
    {
        return array_map(intval(...), array_values($this->validated('roles', [])));
    }

    /**
     * The ticked permissions.
     *
     * @return list<PermissionName>
     */
    public function permissionNames(): array
    {
        return array_map(PermissionName::from(...), array_values($this->validated('permissions', [])));
    }
}
