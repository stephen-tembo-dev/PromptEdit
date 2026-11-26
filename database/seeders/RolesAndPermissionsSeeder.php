<?php

namespace Database\Seeders;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Models\User\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every role and permission from the enums. Safe to run repeatedly, so it
 * also runs on each production deploy to pick up new cases.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach (RoleName::cases() as $role) {
            Role::findOrCreate($role->value)->syncPermissions($role->permissions());
        }
    }
}
