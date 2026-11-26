<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checks the Buy credits form. The page sends a new idempotency key with each
 * purchase, so a double click or a resent form only adds the credits once.
 */
class PurchaseCreditsRequest extends FormRequest
{
    /** The amounts users can pick on the Buy credits page, in whole dollars. */
    public const PRESET_DOLLARS = [5, 10, 25];

    /**
     * Anyone signed in can buy credits.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The rules for the form fields.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount_dollars' => ['required', 'integer', Rule::in(self::PRESET_DOLLARS)],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
