<?php

namespace App\Models\Billing;

use App\Models\Generations\Generation;
use App\Traits\Immutable;
use Database\Factories\Billing\RateCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One set of Pixelforge prices, in nanodollars per million tokens.
 * Each picture points to the prices it was made with.
 *
 * @property int $id
 * @property int $input_text_nano_per_million
 * @property int $input_image_nano_per_million
 * @property int $output_nano_per_million
 * @property int $reasoning_nano_per_million
 */
#[Fillable([
    'input_text_nano_per_million',
    'input_image_nano_per_million',
    'output_nano_per_million',
    'reasoning_nano_per_million',
])]
class RateCard extends Model
{
    /** @use HasFactory<RateCardFactory> */
    use HasFactory, Immutable;

    public const UPDATED_AT = null;

    /**
     * The pictures charged at these prices.
     *
     * @return HasMany<Generation, $this>
     */
    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_text_nano_per_million' => 'integer',
            'input_image_nano_per_million' => 'integer',
            'output_nano_per_million' => 'integer',
            'reasoning_nano_per_million' => 'integer',
        ];
    }
}
