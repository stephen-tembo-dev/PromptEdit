<?php

namespace App\Http\Resources\User;

use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

/**
 * Turns a user into the data the users pages show: who they are and what they
 * can do.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * The user data the pages show. "permissions" only lists the ones given to
     * the user directly; the rest come from their roles.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => $this->whenLoaded('permissions', fn (): array => $this->permissions->map(fn (Permission $permission): string => $permission->name)->values()->all()),
            'is_current_user' => $request->user()->is($this->resource),
            'can_update_access' => $request->user()->can('updateAccess', $this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
