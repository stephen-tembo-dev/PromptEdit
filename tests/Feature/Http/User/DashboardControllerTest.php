<?php

namespace Tests\Feature\Http\User;

use App\Models\Billing\LedgerEntry;
use App\Models\Generations\Generation;
use App\Models\User\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_stats_count_only_the_users_own_images_and_charges(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $user = User::factory()->create();

        $lastMonth = Generation::factory()->for($user)->succeeded()->create(['completed_at' => '2026-10-30 09:00:00']);
        LedgerEntry::factory()->for($lastMonth)->charge()->create(['amount_nano' => 100_000_000, 'created_at' => '2026-10-30 09:00:00']);

        $thisMonth = Generation::factory()->for($user)->succeeded()->create();
        LedgerEntry::factory()->for($thisMonth)->charge()->create(['amount_nano' => 50_000_000]);

        Generation::factory()->for($user)->failed()->create();
        Generation::factory()->for($user)->processing()->create();

        $someoneElses = Generation::factory()->succeeded()->create();
        LedgerEntry::factory()->for($someoneElses)->charge()->create(['amount_nano' => 900_000_000]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.images', 2)
                ->where('stats.images_this_month', 1)
                ->where('stats.spent', '$0.1500')
                ->where('stats.spent_this_month', '$0.0500')
                ->where('stats.average_cost', '$0.0750')
                ->where('stats.unfinished', 1)
                ->where('hasUnfinished', true)
                ->has('recent', 3));
    }

    public function test_a_new_user_sees_zeros_and_no_average(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.images', 0)
                ->where('stats.spent', '$0.0000')
                ->where('stats.average_cost', null)
                ->where('hasUnfinished', false)
                ->has('recent', 0));
    }

    public function test_a_flashed_notify_message_is_shared_with_the_next_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['notify' => ['type' => 'success', 'content' => 'Saved']])
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('notify.type', 'success')
                ->where('notify.content', 'Saved'));
    }
}
