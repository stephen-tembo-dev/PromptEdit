<?php

namespace App\Services\Billing;

use App\Enums\Billing\ReconciliationStatus;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use App\Services\Pixelforge\PixelforgeService;
use App\Support\Data\Billing\ReconciliationDay;
use App\Support\Data\Billing\ReconciliationReport;
use App\Support\Data\Billing\TokenRates;
use App\Support\Data\Billing\TokenUsage;
use App\Support\Data\Billing\UserSpend;
use App\Support\Data\Pixelforge\UsageDay;
use App\Support\Exceptions\Pixelforge\PixelforgeException;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Builds the admin report: what users paid, what Pixelforge charged us, and
 * the profit, for each day and each user.
 *
 * Each day is also checked against Pixelforge's own figures (GET /v1/usage).
 * Both sides count only successful pictures, by the UTC day they finished,
 * so a finished day should match exactly.
 */
class ReconciliationService
{
    /** The longest date range Pixelforge's usage endpoint accepts. */
    public const MAX_DAYS = 92;

    public function __construct(
        private GenerationRepositoryInterface $generations,
        private PricingService $pricing,
        private PixelforgeService $pixelforge,
    ) {}

    /**
     * Build the report for a range of days, with one row per day (even empty
     * days) and one row per user, biggest spender first.
     */
    public function report(CarbonImmutable $startDate, CarbonImmutable $endDate): ReconciliationReport
    {
        $startDate = $startDate->utc()->startOfDay();
        $endDate = $endDate->utc()->startOfDay();

        $usage = $this->pixelforgeUsage($startDate, $endDate);
        $ours = $this->ourTotals($startDate, $endDate);

        $days = [];

        foreach (CarbonPeriodImmutable::create($startDate, $endDate) as $date) {
            $day = $date->toDateString();
            $totals = $ours['days'][$day] ?? null;

            $days[] = $this->reconcile(
                date: $day,
                generations: $totals['generations'] ?? 0,
                tokens: $totals['tokens'] ?? TokenUsage::none(),
                providerCostNano: $totals['provider_cost_nano'] ?? 0,
                chargedNano: $totals['charged_nano'] ?? 0,
                pixelforge: $usage === null ? null : ($usage[$day] ?? new UsageDay($day, 0, TokenUsage::none(), 0)),
                usageAvailable: $usage !== null,
            );
        }

        $users = array_values($ours['users']);
        usort($users, fn (UserSpend $a, UserSpend $b): int => $b->chargedNano <=> $a->chargedNano ?: $a->userId <=> $b->userId);

        return new ReconciliationReport($startDate, $endDate, $days, $users, $usage !== null);
    }

    /**
     * Add up our own numbers per day and per user.
     *
     * The database gives us tokens already summed for pictures that share the
     * same prices, so we price each group once instead of each picture.
     * Because the rates divide evenly, the answer is the same either way.
     *
     * @return array{days: array<string, array{generations: int, tokens: TokenUsage, provider_cost_nano: int, charged_nano: int}>, users: array<int, UserSpend>}
     */
    private function ourTotals(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $days = [];
        $users = [];

        foreach ($this->generations->succeededTotalsByDay($startDate, $endDate) as $row) {
            $tokens = new TokenUsage($row->input_text_tokens, $row->input_image_tokens, $row->output_tokens, $row->reasoning_tokens);
            $cost = $this->pricing->providerCostNano(
                new TokenRates($row->input_text_nano_per_million, $row->input_image_nano_per_million, $row->output_nano_per_million, $row->reasoning_nano_per_million),
                $tokens,
            );

            $day = $days[$row->day] ?? ['generations' => 0, 'tokens' => TokenUsage::none(), 'provider_cost_nano' => 0, 'charged_nano' => 0];
            $days[$row->day] = [
                'generations' => $day['generations'] + $row->generations,
                'tokens' => $day['tokens']->plus($tokens),
                'provider_cost_nano' => $day['provider_cost_nano'] + $cost,
                'charged_nano' => $day['charged_nano'] + $row->charged_nano,
            ];

            $user = $users[$row->user_id] ?? null;
            $users[$row->user_id] = new UserSpend(
                userId: $row->user_id,
                name: $row->user_name,
                email: $row->user_email,
                generations: ($user->generations ?? 0) + $row->generations,
                providerCostNano: ($user->providerCostNano ?? 0) + $cost,
                chargedNano: ($user->chargedNano ?? 0) + $row->charged_nano,
            );
        }

        return ['days' => $days, 'users' => $users];
    }

    /**
     * Compare one day of our numbers with Pixelforge's and decide its status:
     * matched, mismatched, pending (today isn't over yet) or unavailable
     * (we couldn't reach Pixelforge).
     */
    private function reconcile(
        string $date,
        int $generations,
        TokenUsage $tokens,
        int $providerCostNano,
        int $chargedNano,
        ?UsageDay $pixelforge,
        bool $usageAvailable,
    ): ReconciliationDay {
        $status = match (true) {
            ! $usageAvailable => ReconciliationStatus::Unavailable,
            $pixelforge->generations === $generations
                && $pixelforge->tokens->equals($tokens)
                && $pixelforge->costNano === $providerCostNano => ReconciliationStatus::Matched,
            $date === CarbonImmutable::now('UTC')->toDateString() => ReconciliationStatus::Pending,
            default => ReconciliationStatus::Mismatched,
        };

        return new ReconciliationDay($date, $generations, $tokens, $providerCostNano, $chargedNano, $pixelforge, $status);
    }

    /**
     * Pixelforge's numbers for the range. Returns null if Pixelforge can't be
     * reached, so the report can still show our own numbers instead of crashing.
     *
     * @return array<string, UsageDay>|null
     */
    private function pixelforgeUsage(CarbonImmutable $startDate, CarbonImmutable $endDate): ?array
    {
        try {
            return $this->pixelforge->fetchUsage($startDate, $endDate);
        } catch (PixelforgeException $exception) {
            Log::warning('Could not load Pixelforge usage for the admin report.', ['message' => $exception->getMessage()]);

            return null;
        }
    }
}
