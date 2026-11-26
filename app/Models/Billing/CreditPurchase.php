<?php

namespace App\Models\Billing;

use App\Models\User\User;
use App\Traits\Immutable;
use Database\Factories\Billing\CreditPurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One "Buy credits" purchase. It's pretend, so no real payment is taken.
 *
 * @property int $id
 * @property int $user_id
 * @property int $amount_nano
 * @property string $idempotency_key
 */
#[Fillable(['amount_nano', 'idempotency_key'])]
class CreditPurchase extends Model
{
    /** @use HasFactory<CreditPurchaseFactory> */
    use HasFactory, Immutable;

    public const UPDATED_AT = null;

    /**
     * Get the user who bought the credits.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_nano' => 'integer',
        ];
    }
}
