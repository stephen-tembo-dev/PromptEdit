<?php

namespace App\Http\Resources\Generations;

use App\Enums\Billing\LedgerEntryType;
use App\Enums\Generations\GenerationStatus;
use App\Models\Generations\Generation;
use App\Support\Money\NanoDollars;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Turns a picture into the data the pages show. Prices are turned into text
 * here on the server, so the browser never does any money sums.
 *
 * @mixin Generation
 */
class GenerationResource extends JsonResource
{
    /**
     * The picture data the pages show. Expired image links are hidden.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageExpired = $this->image_url_expires_at?->isPast() ?? false;

        return [
            'id' => $this->uuid,
            'prompt' => $this->prompt,
            'size' => $this->size->value,
            'quality' => $this->quality->value,
            'status' => $this->status->value,
            'is_waiting_for_slot' => $this->isWaitingForSlot(),
            'is_finished' => $this->isFinished(),
            'reference_images' => $this->whenLoaded('referenceImages', fn () => $this->referenceImages->pluck('url')->all()),
            'estimated_charge' => NanoDollars::format($this->estimated_charge_nano),
            'held_charge' => $this->formattedLedgerAmount(LedgerEntryType::Hold),
            'actual_charge' => $this->actualCharge(),
            'image_url' => $imageExpired ? null : $this->image_url,
            'image_expired' => $imageExpired,
            'error_message' => $this->error_message,
            'usage' => $this->status === GenerationStatus::Succeeded ? [
                'input_text_tokens' => $this->input_text_tokens,
                'input_image_tokens' => $this->input_image_tokens,
                'output_tokens' => $this->output_tokens,
                'reasoning_tokens' => $this->reasoning_tokens,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }

    /**
     * What the user actually paid: the charge if it succeeded, $0 if it failed,
     * or null if it's still running.
     */
    private function actualCharge(): ?string
    {
        return match ($this->status) {
            GenerationStatus::Succeeded => $this->formattedLedgerAmount(LedgerEntryType::Charge),
            GenerationStatus::Failed => NanoDollars::format(0),
            default => null,
        };
    }

    /**
     * The amount of one of the picture's money lines, ready to show, or null if there isn't one.
     */
    private function formattedLedgerAmount(LedgerEntryType $type): ?string
    {
        $entry = $this->ledgerEntries->firstWhere('type', $type);

        return $entry === null ? null : NanoDollars::format($entry->amount_nano);
    }
}
