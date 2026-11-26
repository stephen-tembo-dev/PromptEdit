<?php

namespace App\Http\Requests\Billing;

use App\Services\Billing\ReconciliationService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Checks the date range for the admin report. If no dates are given, it shows
 * the last 30 days. The range can be 92 days at most, the same as Pixelforge.
 * Who can open the report is checked on the route, not here.
 */
class ReconciliationReportRequest extends FormRequest
{
    public const DEFAULT_DAYS = 30;

    /**
     * Access is already checked on the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The rules for the dates.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:start_date'],
        ];
    }

    /**
     * One more check after the rules pass: the range can't be longer than 92 days.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->startDate()->diffInDays($this->endDate()) + 1 > ReconciliationService::MAX_DAYS) {
                    $validator->errors()->add('start_date', 'The report covers at most '.ReconciliationService::MAX_DAYS.' days.');
                }
            },
        ];
    }

    /**
     * The last day of the report. Defaults to today (UTC).
     */
    public function endDate(): CarbonImmutable
    {
        $endDate = $this->input('end_date');

        return $endDate === null
            ? CarbonImmutable::today('UTC')
            : CarbonImmutable::createFromFormat('!Y-m-d', $endDate, 'UTC');
    }

    /**
     * The first day of the report. Defaults to 30 days before the end date.
     */
    public function startDate(): CarbonImmutable
    {
        $startDate = $this->input('start_date');

        return $startDate === null
            ? $this->endDate()->subDays(self::DEFAULT_DAYS - 1)
            : CarbonImmutable::createFromFormat('!Y-m-d', $startDate, 'UTC');
    }
}
