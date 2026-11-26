<?php

namespace App\Support\Data\Pixelforge;

/**
 * One page of results from Pixelforge's list of pictures.
 */
final readonly class GenerationPage
{
    /**
     * @param  list<GenerationSnapshot>  $generations
     */
    public function __construct(
        public array $generations,
        public bool $hasMore,
        public ?string $nextCursor,
    ) {}
}
