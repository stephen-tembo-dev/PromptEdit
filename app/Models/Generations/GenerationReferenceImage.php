<?php

namespace App\Models\Generations;

use Database\Factories\Generations\GenerationReferenceImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reference image link on a picture (up to four per picture).
 * Pixelforge never downloads it, but charges 765 image tokens for each one.
 *
 * @property int $id
 * @property int $generation_id
 * @property int $position
 * @property string $url
 */
#[Fillable(['position', 'url'])]
#[Table(timestamps: false)]
class GenerationReferenceImage extends Model
{
    /** @use HasFactory<GenerationReferenceImageFactory> */
    use HasFactory;

    /**
     * The picture this image belongs to.
     *
     * @return BelongsTo<Generation, $this>
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
