<?php

namespace Tests\Feature\Http\User;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Models\User\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->admin()->create();
    }

    public function test_users_without_the_permission_cannot_open_the_roles_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_the_list_shows_each_role_with_its_user_count(): void
    {
        $support = Role::factory()->create(['name' => 'Support']);
        User::factory()->count(2)->create()->each->assignRole($support);

        $this->actingAs($this->manager)
            ->get('/admin/roles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('roles/Index')
                ->has('roles', 3)
                ->where('roles.0.name', 'Admin')
                ->where('roles.0.is_built_in', true)
                ->where('roles.0.can_update', false)
                ->where('roles.0.users_count', 1)
                ->where('roles.1.name', 'Customer')
                ->where('roles.1.users_count', 3)
                ->where('roles.2.name', 'Support')
                ->where('roles.2.users_count', 2)
                ->where('roles.2.can_update', true));
    }

    public function test_a_new_role_gives_its_permissions_to_whoever_has_it(): void
    {
        $this->actingAs($this->manager)
            ->post('/admin/roles', ['name' => 'Finance', 'permissions' => [PermissionName::ViewAdminReport->value]])
            ->assertRedirect('/admin/roles')
            ->assertSessionHas('notify.type', 'success');

        $user = User::factory()->create();
        $user->assignRole('Finance');

        $this->assertTrue($user->can(PermissionName::ViewAdminReport->value));
        $this->assertFalse($user->can(PermissionName::ManageUsers->value));
    }

    public function test_a_role_name_must_be_unique_and_not_a_built_in_name_in_any_case(): void
    {
        Role::factory()->create(['name' => 'Support']);

        $this->actingAs($this->manager)
            ->post('/admin/roles', ['name' => 'Support', 'permissions' => []])
            ->assertSessionHasErrors(['name' => 'There is already a role with this name.']);

        $this->actingAs($this->manager)
            ->post('/admin/roles', ['name' => 'ADMIN', 'permissions' => []])
            ->assertSessionHasErrors(['name' => 'This name belongs to a built-in role.']);

        $this->assertSame(['admin', 'customer', 'Support'], Role::orderBy('id')->pluck('name')->all());
    }

    public function test_a_custom_role_can_be_renamed_and_its_permissions_replaced(): void
    {
        $role = Role::factory()->create(['name' => 'Support']);
        $role->givePermissionTo(PermissionName::ViewAdminReport);

        $this->actingAs($this->manager)
            ->put("/admin/roles/{$role->id}", ['name' => 'Customer support', 'permissions' => [PermissionName::ManageUsers->value]])
            ->assertRedirect('/admin/roles');

        $role->refresh();
        $this->assertSame('Customer support', $role->name);
        $this->assertSame([PermissionName::ManageUsers->value], $role->permissions->pluck('name')->all());
    }

    public function test_built_in_roles_cannot_be_edited_or_deleted(): void
    {
        $admin = Role::findByName(RoleName::Admin->value);

        $this->actingAs($this->manager)->get("/admin/roles/{$admin->id}/edit")->assertForbidden();
        $this->actingAs($this->manager)->put("/admin/roles/{$admin->id}", ['name' => 'Boss', 'permissions' => []])->assertForbidden();
        $this->actingAs($this->manager)->delete("/admin/roles/{$admin->id}")->assertForbidden();

        $this->assertSame(RoleName::Admin->value, $admin->fresh()->name);
        $this->assertCount(count(RoleName::Admin->permissions()), $admin->fresh()->permissions);
    }

    public function test_deleting_a_role_takes_its_permissions_away_from_its_users(): void
    {
        $role = Role::factory()->create(['name' => 'Finance']);
        $role->givePermissionTo(PermissionName::ViewAdminReport);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($this->manager)
            ->delete("/admin/roles/{$role->id}")
            ->assertRedirect('/admin/roles');

        $this->assertModelMissing($role);
        $this->assertFalse($user->fresh()->can(PermissionName::ViewAdminReport->value));
    }
}
