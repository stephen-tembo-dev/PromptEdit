<?php

use App\Enums\User\RoleName;
use App\Models\User\Role;
use App\Models\User\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Dashboard, Generate, History and Credits now need a permission, which
     * new sign-ups get through the Customer role. Everyone who signed up before
     * that gets the role here, so nobody loses access on the deploy. The seeder
     * runs first because the role has to exist, and this runs before it would.
     */
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();

        $customer = Role::findByName(RoleName::Customer->value);

        DB::table(config('permission.table_names.model_has_roles'))->insertUsing(
            ['role_id', 'model_type', 'model_id'],
            DB::table('users')->selectRaw('?, ?, id', [$customer->id, (new User)->getMorphClass()]),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * Nothing to undo: without the permission checks, having the role changes nothing.
     */
    public function down(): void
    {
        //
    }
};
