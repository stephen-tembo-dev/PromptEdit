<?php

namespace Tests\Feature;

use App\Enums\User\RoleName;
use App\Models\User\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Login')
                ->where('canRegister', true)
                ->where('canResetPassword', true));
    }

    public function test_users_can_log_in(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_log_in_with_a_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_registration_screen_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/Register'));
    }

    public function test_new_users_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertSame([RoleName::Customer->value], User::sole()->getRoleNames()->all());
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->get('/forgot-password')
            ->assertInertia(fn (Assert $page) => $page->component('auth/ForgotPassword'));

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get("/reset-password/{$notification->token}?email={$user->email}")
                ->assertInertia(fn (Assert $page) => $page
                    ->component('auth/ResetPassword')
                    ->where('token', $notification->token)
                    ->where('email', $user->email));

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasNoErrors()->assertRedirect('/login');

            return true;
        });
    }

    public function test_users_can_update_their_profile_information(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/profile-information', ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_profile_email_must_not_belong_to_another_user(): void
    {
        $taken = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/profile-information', ['name' => $user->name, 'email' => $taken->email])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'email');

        $this->assertNotSame($taken->email, $user->refresh()->email);
    }

    public function test_users_can_change_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_changing_the_password_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/user/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_see_the_dashboard_with_shared_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('auth.user.email', $user->email)
                ->missing('auth.user.password'));
    }
}
