<?php

namespace Database\Seeders;

use App\Models\Billing\CreditPurchase;
use App\Models\User\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        CreditPurchase::factory()->for($user)->create();

        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);
    }
}
