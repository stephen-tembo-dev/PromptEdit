<?php

namespace Tests\Feature\Policies\User;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Models\User\Role;
use App\Models\User\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementPoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_only_user_managers_can_change_access_and_never_their_own(): void
    {
        $manager = User::factory()->create()->givePermissionTo(PermissionName::ManageUsers);
        $customer = User::factory()->create();

        $this->assertTrue($manager->can('updateAccess', $customer));
        $this->assertFalse($manager->can('updateAccess', $manager));
        $this->assertFalse($customer->can('updateAccess', $manager));
    }

    public function test_only_role_managers_can_change_roles_and_never_built_in_ones(): void
    {
        $manager = User::factory()->create()->givePermissionTo(PermissionName::ManageRoles);
        $customer = User::factory()->create();
        $custom = Role::factory()->create();
        $builtIn = Role::findByName(RoleName::Admin->value);

        $this->assertTrue($manager->can('update', $custom));
        $this->assertTrue($manager->can('delete', $custom));
        $this->assertFalse($manager->can('update', $builtIn));
        $this->assertFalse($manager->can('delete', $builtIn));
        $this->assertFalse($customer->can('update', $custom));
        $this->assertFalse($customer->can('delete', $custom));
    }
}
