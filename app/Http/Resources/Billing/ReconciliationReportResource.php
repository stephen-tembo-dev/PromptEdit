<?php

namespace App\Http\Resources\Billing;

use App\Support\Data\Billing\ReconciliationDay;
use App\Support\Data\Billing\ReconciliationReport;
use App\Support\Data\Billing\UserSpend;
use App\Support\Money\NanoDollars;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Turns the admin report into the data the page shows.
 * Totals show 4 decimal places, like the rest of the app. Differences show all
 * 9, because a gap between us and Pixelforge can be smaller than a cent.
 *
 * @mixin ReconciliationReport
 */
class ReconciliationReportResource extends JsonResource
{
    /**
     * The report data the page shows.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pixelforgeCost = $this->pixelforgeCostNano();

        return [
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
            'pixelforge_available' => $this->pixelforgeAvailable,
            'totals' => [
                'charged' => NanoDollars::format($this->chargedNano()),
                'provider_cost' => NanoDollars::format($this->providerCostNano()),
                'pixelforge_cost' => $pixelforgeCost === null ? null : NanoDollars::format($pixelforgeCost),
                'margin' => NanoDollars::format($this->marginNano()),
                'mismatched_days' => $this->mismatchedDayCount(),
            ],
            // Newest day first. Days with nothing on either side are left out.
            'days' => array_values(array_map(fn (ReconciliationDay $day): array => [
                'date' => $day->date,
                'status' => $day->status->value,
                'generations' => $day->generations,
                'pixelforge_generations' => $day->pixelforge?->generations,
                'charged' => NanoDollars::format($day->chargedNano),
                'provider_cost' => NanoDollars::format($day->providerCostNano),
                'pixelforge_cost' => $day->pixelforge === null ? null : NanoDollars::format($day->pixelforge->costNano),
                'cost_difference' => $day->costDifferenceNano() === null ? null : NanoDollars::format($day->costDifferenceNano(), 9),
                'tokens_match' => $day->pixelforge === null ? null : $day->pixelforge->tokens->equals($day->tokens),
                'margin' => NanoDollars::format($day->marginNano()),
            ], array_reverse(array_filter($this->days, fn (ReconciliationDay $day): bool => $day->hasActivity())))),
            'users' => array_map(fn (UserSpend $user): array => [
                'id' => $user->userId,
                'name' => $user->name,
                'email' => $user->email,
                'generations' => $user->generations,
                'charged' => NanoDollars::format($user->chargedNano),
                'provider_cost' => NanoDollars::format($user->providerCostNano),
                'margin' => NanoDollars::format($user->marginNano()),
            ], $this->users),
        ];
    }
}
