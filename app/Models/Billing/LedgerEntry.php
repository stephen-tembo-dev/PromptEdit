<?php

namespace App\Models\Billing;

use App\Enums\Billing\LedgerEntryType;
use App\Models\Generations\Generation;
use App\Traits\Immutable;
use Database\Factories\Billing\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One money line for a picture: a hold, a charge or a release.
 * Lines are only ever added, never changed or deleted.
 *
 * @property int $id
 * @property int $generation_id
 * @property LedgerEntryType $type
 * @property int $amount_nano
 */
#[Fillable(['type', 'amount_nano'])]
class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory, Immutable;

    public const UPDATED_AT = null;

    /**
     * The picture this line belongs to.
     *
     * @return BelongsTo<Generation, $this>
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    /**
     * The amount with a minus sign for holds and charges, plus for releases.
     */
    public function signedAmountNano(): int
    {
        return $this->type->sign() * $this->amount_nano;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'amount_nano' => 'integer',
        ];
    }
}
