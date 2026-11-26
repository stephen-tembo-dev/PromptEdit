<?php

namespace App\Http\Resources\Billing;

use App\Models\Billing\CreditPurchase;
use App\Support\Money\NanoDollars;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Turns a credit purchase into the data the Buy credits page shows.
 *
 * @mixin CreditPurchase
 */
class CreditPurchaseResource extends JsonResource
{
    /**
     * The purchase data the page shows.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => NanoDollars::format($this->amount_nano, 2),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
