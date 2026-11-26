<?php

namespace App\Http\Resources\User;

use App\Models\User\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

/**
 * Turns a role into the data the roles and users pages show.
 *
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * The role data the pages show. Permissions are sent as their PermissionName values.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->label(),
            'is_built_in' => $this->isBuiltIn(),
            'permissions' => $this->whenLoaded('permissions', fn (): array => $this->permissions->map(fn (Permission $permission): string => $permission->name)->values()->all()),
            'users_count' => $this->whenCounted('users'),
            'can_update' => $request->user()->can('update', $this->resource),
        ];
    }
}
