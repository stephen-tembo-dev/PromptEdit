<?php

namespace App\Models\User;

use App\Enums\User\RoleName;
use Database\Factories\User\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * A role from spatie/laravel-permission. We extend it so a role knows whether
 * it is one of the built-in ones from RoleName. config/permission.php points
 * the package at this class.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property int|null $users_count
 */
class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * The matching RoleName case, or null for a role made on the roles page.
     */
    public function builtIn(): ?RoleName
    {
        return RoleName::tryFrom($this->name);
    }

    /**
     * True for the roles the seeder owns. Those can't be edited or deleted here.
     */
    public function isBuiltIn(): bool
    {
        return $this->builtIn() !== null;
    }

    /**
     * The name to show people.
     */
    public function label(): string
    {
        return $this->builtIn()?->label() ?? $this->name;
    }
}
