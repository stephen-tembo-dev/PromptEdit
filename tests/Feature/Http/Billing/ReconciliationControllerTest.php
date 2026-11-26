<?php

namespace Tests\Feature\Http\Billing;

use App\Models\User\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class ReconciliationControllerTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00', 'UTC'));
        Http::fake(fn (Request $request) => Http::response($this->usageBody($request['start_date'], $request['end_date'])));
    }

    public function test_users_without_the_permission_cannot_see_the_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/report')
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_admins_see_the_last_30_days_by_default(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/report')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('billing/Reconciliation')
                ->where('auth.can.viewAdminReport', true)
                ->where('report.start_date', '2026-10-22')
                ->where('report.end_date', '2026-11-20')
                ->where('report.pixelforge_available', true)
                ->where('report.totals.charged', '$0.0000')
                ->where('report.totals.mismatched_days', 0));
    }

    public function test_the_range_is_limited_to_what_pixelforge_can_report(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/report?start_date=2026-08-01&end_date=2026-11-20')
            ->assertSessionHasErrors('start_date');

        $this->actingAs($admin)
            ->get('/admin/report?start_date=2026-11-20&end_date=2026-11-21')
            ->assertSessionHasErrors('end_date');

        Http::assertNothingSent();
    }
}
