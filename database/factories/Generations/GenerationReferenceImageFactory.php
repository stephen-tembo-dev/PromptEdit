<?php

namespace Database\Factories\Generations;

use App\Models\Generations\Generation;
use App\Models\Generations\GenerationReferenceImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GenerationReferenceImage>
 */
class GenerationReferenceImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'generation_id' => Generation::factory(),
            'position' => 0,
            'url' => 'https://example.com/references/'.Str::lower(Str::random(12)).'.png',
        ];
    }
}
