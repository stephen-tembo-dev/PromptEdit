<?php

namespace Tests\Feature\Http\User;

use App\Enums\User\PermissionName;
use App\Enums\User\RoleName;
use App\Models\User\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->admin()->create(['name' => 'Ada Admin', 'email' => 'ada@example.com']);
    }

    public function test_users_without_the_permission_cannot_open_user_management(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_the_list_shows_users_with_their_roles_and_can_be_searched(): void
    {
        User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

        $this->actingAs($this->manager)
            ->get('/admin/users?search=grace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->where('auth.can.manageUsers', true)
                ->where('search', 'grace')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'grace@example.com')
                ->has('users.data.0.roles', 1)
                ->where('users.data.0.roles.0.name', 'Customer'));

        $this->actingAs($this->manager)
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 2));
    }

    public function test_the_new_user_form_starts_with_the_customer_role_ticked(): void
    {
        $customer = Role::findByName(RoleName::Customer->value);

        $this->actingAs($this->manager)
            ->get('/admin/users/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Create')
                ->where('defaultRoleIds', [$customer->id]));
    }

    public function test_a_manager_can_create_a_user_with_roles_and_permissions(): void
    {
        $support = Role::factory()->create(['name' => 'Support']);

        $response = $this->actingAs($this->manager)->post('/admin/users', [
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'password' => 'a-long-password-1',
            'password_confirmation' => 'a-long-password-1',
            'roles' => [$support->id],
            'permissions' => [PermissionName::ViewAdminReport->value],
        ]);

        $user = User::where('email', 'grace@example.com')->firstOrFail();

        $response->assertRedirect("/admin/users/{$user->id}/edit")->assertSessionHas('notify.type', 'success');
        $this->assertTrue(Hash::check('a-long-password-1', $user->password));
        $this->assertTrue($user->hasRole($support));
        $this->assertTrue($user->hasDirectPermission(PermissionName::ViewAdminReport));
    }

    public function test_creating_a_user_needs_a_name_a_unique_email_and_a_confirmed_password(): void
    {
        $this->actingAs($this->manager)
            ->post('/admin/users', [
                'name' => '',
                'email' => 'ada@example.com',
                'password' => 'a-long-password-1',
                'password_confirmation' => 'something-else',
                'roles' => [],
                'permissions' => [],
            ])
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email has already been taken.',
                'password' => 'The password field confirmation does not match.',
            ]);

        $this->assertSame(1, User::count());
    }

    public function test_only_real_roles_and_permissions_can_be_given(): void
    {
        $this->actingAs($this->manager)
            ->post('/admin/users', [
                'name' => 'Grace Hopper',
                'email' => 'grace@example.com',
                'password' => 'a-long-password-1',
                'password_confirmation' => 'a-long-password-1',
                'roles' => [999],
                'permissions' => ['delete everything'],
            ])
            ->assertSessionHasErrors(['roles.0', 'permissions.0']);

        $this->assertDatabaseMissing('users', ['email' => 'grace@example.com']);
    }

    public function test_a_manager_can_replace_another_users_access(): void
    {
        $user = User::factory()->admin()->create();
        $support = Role::factory()->create(['name' => 'Support']);

        $this->actingAs($this->manager)
            ->put("/admin/users/{$user->id}", [
                'roles' => [$support->id],
                'permissions' => [PermissionName::ManageRoles->value],
            ])
            ->assertSessionHas('notify.type', 'success');

        $user->refresh();
        $this->assertSame(['Support'], $user->getRoleNames()->all());
        $this->assertFalse($user->hasRole(RoleName::Admin));
        $this->assertSame([PermissionName::ManageRoles->value], $user->getDirectPermissions()->pluck('name')->all());
    }

    public function test_nobody_can_change_their_own_access(): void
    {
        $this->actingAs($this->manager)
            ->get("/admin/users/{$this->manager->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Edit')
                ->where('user.can_update_access', false));

        $this->actingAs($this->manager)
            ->put("/admin/users/{$this->manager->id}", ['roles' => [], 'permissions' => []])
            ->assertForbidden();

        $this->assertTrue($this->manager->fresh()->hasRole(RoleName::Admin));
    }
}
