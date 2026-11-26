<?php

namespace App\Http\Resources\Billing;

use App\Enums\Billing\TransactionKind;
use App\Support\Money\NanoDollars;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Turns one row from TransactionRepository into a line on the Reports page.
 * The amount carries its sign: "+" adds to the balance, "−" takes away.
 *
 * @property object{kind: string, source_id: int, amount_nano: int, created_at: string, generation_uuid: string|null, prompt: string|null} $resource
 */
class TransactionResource extends JsonResource
{
    /**
     * The line as the page shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kind = TransactionKind::from($this->resource->kind);

        return [
            'id' => "{$kind->value}-{$this->resource->source_id}",
            'kind' => $kind->value,
            'label' => $kind->label(),
            'amount' => ($kind->sign() > 0 ? '+' : '−').NanoDollars::format((int) $this->resource->amount_nano),
            'generation_id' => $this->resource->generation_uuid,
            'prompt' => $this->resource->prompt,
            'created_at' => CarbonImmutable::parse($this->resource->created_at)->toIso8601String(),
        ];
    }
}
