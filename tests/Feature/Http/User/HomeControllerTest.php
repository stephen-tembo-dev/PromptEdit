<?php

namespace Tests\Feature\Http\User;

use App\Enums\User\PermissionName;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_welcome_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('hasNoAccess', false));
    }

    public function test_customers_are_sent_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect('/dashboard');
    }

    public function test_admins_are_sent_to_the_admin_report_even_when_they_are_also_customers(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertRedirect('/admin/report');

        $this->actingAs(User::factory()->withoutRoles()->admin()->create())
            ->get('/')
            ->assertRedirect('/admin/report');
    }

    public function test_users_go_to_the_first_page_their_permissions_open(): void
    {
        $userManager = User::factory()->withoutRoles()->create()->givePermissionTo(PermissionName::ManageUsers);
        $historyOnly = User::factory()->withoutRoles()->create()->givePermissionTo(PermissionName::ViewHistory);

        $this->actingAs($userManager)->get('/')->assertRedirect('/admin/users');
        $this->actingAs($historyOnly)->get('/')->assertRedirect('/generations');
    }

    public function test_users_with_no_access_see_a_note_instead_of_a_403(): void
    {
        $this->actingAs(User::factory()->withoutRoles()->create())
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('hasNoAccess', true));
    }

    public function test_signed_in_users_opening_the_login_page_are_sent_home(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/login')
            ->assertRedirect('/');
    }
}
