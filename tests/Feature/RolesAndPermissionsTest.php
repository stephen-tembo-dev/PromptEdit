<?php

namespace Tests\Feature;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Models\User\Role;
use App\Models\User\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_can_run_on_every_deploy_without_duplicating_rows(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(count(RoleName::cases()), Role::count());
        $this->assertSame(count(PermissionName::cases()), Permission::count());
        $this->assertTrue(Role::findByName(RoleName::Admin->value)->hasPermissionTo(PermissionName::ViewAdminReport));
    }

    /**
     * Each page and the permission that opens it.
     *
     * @return array<string, array{string, string, PermissionName}>
     */
    public static function customerPages(): array
    {
        return [
            'dashboard' => ['get', '/dashboard', PermissionName::ViewDashboard],
            'generate form' => ['get', '/generations/create', PermissionName::GenerateImages],
            'live quote' => ['post', '/generations/quote', PermissionName::GenerateImages],
            'submit' => ['post', '/generations', PermissionName::GenerateImages],
            'history' => ['get', '/generations', PermissionName::ViewHistory],
            'credits' => ['get', '/credits', PermissionName::ManageCredits],
            'buy credits' => ['post', '/credits', PermissionName::ManageCredits],
            'reports' => ['get', '/reports', PermissionName::ViewReports],
        ];
    }

    #[DataProvider('customerPages')]
    public function test_each_page_needs_its_own_permission(string $method, string $uri, PermissionName $permission): void
    {
        $user = User::factory()->withoutRoles()->create();

        $this->actingAs($user)->call($method, $uri)->assertForbidden();

        $user->givePermissionTo($permission);

        $this->assertNotSame(403, $this->actingAs($user->fresh())->call($method, $uri)->getStatusCode());
    }

    public function test_users_from_before_the_customer_role_keep_their_access_after_the_deploy(): void
    {
        $existing = User::factory()->count(2)->withoutRoles()->create();

        (require database_path('migrations/2026_11_25_225824_give_existing_users_the_customer_role.php'))->up();

        foreach ($existing as $user) {
            $this->assertTrue($user->fresh()->hasRole(RoleName::Customer));
            $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
        }
    }

    public function test_customers_get_the_normal_pages_and_admins_only_the_staff_tools(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertEqualsCanonicalizing(
            [PermissionName::ViewDashboard->value, PermissionName::GenerateImages->value, PermissionName::ViewHistory->value, PermissionName::ManageCredits->value, PermissionName::ViewReports->value],
            Role::findByName(RoleName::Customer->value)->permissions->pluck('name')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [PermissionName::ViewAdminReport->value, PermissionName::ManageUsers->value, PermissionName::ManageRoles->value],
            Role::findByName(RoleName::Admin->value)->permissions->pluck('name')->all(),
        );
    }

    public function test_an_admin_without_the_customer_role_cannot_open_the_customer_pages(): void
    {
        $admin = User::factory()->withoutRoles()->admin()->create();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/generations/create')->assertForbidden();
    }

    public function test_only_admins_can_view_the_admin_report(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->assertTrue($admin->can(PermissionName::ViewAdminReport->value));
        $this->assertFalse($customer->can(PermissionName::ViewAdminReport->value));
    }
}
