<?php

namespace App\Http\Requests\Billing;

use App\Enums\Billing\TransactionKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checks the filter on the Reports page. Holds and releases are shown
 * together, because each release just gives back a hold.
 */
class TransactionReportRequest extends FormRequest
{
    /** The filter values and the kinds of line each one shows. */
    public const FILTERS = [
        'purchases' => [TransactionKind::Purchase],
        'charges' => [TransactionKind::Charge],
        'holds' => [TransactionKind::Hold, TransactionKind::Release],
    ];

    /**
     * Access is already checked on the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The rules for the filter.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(array_keys(self::FILTERS))],
        ];
    }

    /**
     * The chosen filter, or null for everything.
     */
    public function filter(): ?string
    {
        return $this->validated('type');
    }

    /**
     * The kinds of line to show, or null for all of them.
     *
     * @return list<TransactionKind>|null
     */
    public function kinds(): ?array
    {
        return self::FILTERS[$this->filter()] ?? null;
    }
}
