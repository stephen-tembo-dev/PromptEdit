<?php

namespace Database\Factories\User;

use App\Enums\User\RoleName;
use App\Models\User\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Every user starts as a Customer, the same as signing up, so they can
     * open the normal pages. Use withoutRoles() for someone with no access.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            (new RolesAndPermissionsSeeder)->run();

            $user->assignRole(RoleName::Customer);
        });
    }

    /**
     * A user with no roles at all, so no page beyond their own account opens for them.
     */
    public function withoutRoles(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles([]));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Give the user the admin role (an operator who can see the admin report).
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            (new RolesAndPermissionsSeeder)->run();

            $user->assignRole(RoleName::Admin);
        });
    }
}
