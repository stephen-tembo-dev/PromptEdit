<?php

namespace Tests\Feature\Services\Billing;

use App\Enums\Billing\ReconciliationStatus;
use App\Models\Billing\LedgerEntry;
use App\Models\Generations\Generation;
use App\Models\User\User;
use App\Services\Billing\ReconciliationService;
use App\Support\Data\Billing\ReconciliationDay;
use App\Support\Data\Billing\ReconciliationReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class ReconciliationServiceTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    /** 12 x $5 + 1,100 x $40 + 1,000 x $12.50 per million tokens. */
    private const COST_NANO = 56_560_000;

    /** ceil(56,560,000 x 1.25). */
    private const CHARGE_NANO = 70_700_000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00', 'UTC'));
    }

    public function test_a_day_that_agrees_with_pixelforge_to_the_nanodollar_is_matched(): void
    {
        $user = User::factory()->create();
        $this->succeeded($user, '2026-11-18 09:00:00');
        $this->succeeded($user, '2026-11-18 23:59:59');
        $this->fakeUsage(['2026-11-18' => [2, 24, 0, 2_200, 2_000, '0.113120000']]);

        $report = $this->report('2026-11-17', '2026-11-19');
        $day = $this->day($report, '2026-11-18');

        $this->assertSame(ReconciliationStatus::Matched, $day->status);
        $this->assertSame(2, $day->generations);
        $this->assertSame(2 * self::COST_NANO, $day->providerCostNano);
        $this->assertSame(2 * self::CHARGE_NANO, $day->chargedNano);
        $this->assertSame(0, $day->costDifferenceNano());
        $this->assertSame(2 * (self::CHARGE_NANO - self::COST_NANO), $report->marginNano());
        $this->assertSame(ReconciliationStatus::Matched, $this->day($report, '2026-11-17')->status);
        $this->assertSame(0, $report->mismatchedDayCount());

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'usage?start_date=2026-11-17&end_date=2026-11-19')
            && $request->hasHeader('Authorization'));
    }

    public function test_a_past_day_where_pixelforge_billed_more_than_we_recorded_is_a_mismatch(): void
    {
        $this->succeeded(User::factory()->create(), '2026-11-18 09:00:00');
        $this->fakeUsage(['2026-11-18' => [2, 24, 0, 2_200, 2_000, '0.113120000']]);

        $report = $this->report('2026-11-18', '2026-11-18');
        $day = $this->day($report, '2026-11-18');

        $this->assertSame(ReconciliationStatus::Mismatched, $day->status);
        $this->assertSame(self::COST_NANO, $day->costDifferenceNano());
        $this->assertSame(self::CHARGE_NANO - 2 * self::COST_NANO, $report->marginNano(), 'Margin uses what Pixelforge actually billed.');
        $this->assertSame(1, $report->mismatchedDayCount());
    }

    public function test_today_disagreeing_is_pending_because_generations_may_still_be_settling(): void
    {
        $this->fakeUsage(['2026-11-20' => [1, 12, 0, 1_100, 1_000, '0.056560000']]);

        $report = $this->report('2026-11-20', '2026-11-20');

        $this->assertSame(ReconciliationStatus::Pending, $this->day($report, '2026-11-20')->status);
        $this->assertSame(0, $report->mismatchedDayCount());
    }

    public function test_failed_generations_and_ones_completed_outside_the_range_are_left_out(): void
    {
        $user = User::factory()->create();
        Generation::factory()->for($user)->failed()->create(['completed_at' => '2026-11-18 10:00:00']);
        $this->succeeded($user, '2026-11-17 23:59:59');
        $this->succeeded($user, '2026-11-19 00:00:00');
        $this->fakeUsage();

        $report = $this->report('2026-11-18', '2026-11-18');

        $this->assertSame(0, $this->day($report, '2026-11-18')->generations);
        $this->assertSame([], $report->users);
        $this->assertSame(ReconciliationStatus::Matched, $this->day($report, '2026-11-18')->status);
    }

    public function test_spend_is_totalled_per_user_biggest_spender_first(): void
    {
        $light = User::factory()->create();
        $heavy = User::factory()->create();
        $this->succeeded($light, '2026-11-18 09:00:00');
        $this->succeeded($heavy, '2026-11-18 10:00:00');
        $this->succeeded($heavy, '2026-11-19 10:00:00');
        $this->fakeUsage();

        $users = $this->report('2026-11-18', '2026-11-19')->users;

        $this->assertSame([$heavy->id, $light->id], array_map(fn ($user): int => $user->userId, $users));
        $this->assertSame(2, $users[0]->generations);
        $this->assertSame(2 * self::CHARGE_NANO, $users[0]->chargedNano);
        $this->assertSame(2 * (self::CHARGE_NANO - self::COST_NANO), $users[0]->marginNano());
    }

    public function test_the_report_still_shows_our_figures_when_pixelforge_is_unavailable(): void
    {
        $this->succeeded(User::factory()->create(), '2026-11-18 09:00:00');
        Http::fake([self::PIXELFORGE_URL.'/usage*' => Http::response($this->errorBody('api_error', 'service_unavailable', 'Overloaded.'), 503)]);

        $report = $this->report('2026-11-18', '2026-11-18');
        $day = $this->day($report, '2026-11-18');

        $this->assertFalse($report->pixelforgeAvailable);
        $this->assertNull($report->pixelforgeCostNano());
        $this->assertSame(ReconciliationStatus::Unavailable, $day->status);
        $this->assertSame(self::COST_NANO, $day->providerCostNano);
        $this->assertSame(self::CHARGE_NANO - self::COST_NANO, $report->marginNano());
    }

    /**
     * A succeeded generation with a known cost and its charge, as settlement leaves it.
     */
    private function succeeded(User $user, string $completedAt): Generation
    {
        $generation = Generation::factory()->for($user)->succeeded()->create([
            'reasoning_tokens' => 1_000,
            'completed_at' => $completedAt,
        ]);

        LedgerEntry::factory()->for($generation)->charge()->create(['amount_nano' => self::CHARGE_NANO]);

        return $generation;
    }

    /**
     * @param  array<string, array{int, int, int, int, int, string}>  $days
     */
    private function fakeUsage(array $days = []): void
    {
        Http::fake(fn (Request $request) => Http::response($this->usageBody($request['start_date'], $request['end_date'], $days)));
    }

    private function report(string $startDate, string $endDate): ReconciliationReport
    {
        return app(ReconciliationService::class)->report(
            CarbonImmutable::parse($startDate, 'UTC'),
            CarbonImmutable::parse($endDate, 'UTC'),
        );
    }

    private function day(ReconciliationReport $report, string $date): ReconciliationDay
    {
        return collect($report->days)->sole(fn (ReconciliationDay $day): bool => $day->date === $date);
    }
}
