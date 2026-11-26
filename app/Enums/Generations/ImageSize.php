<?php

namespace App\Enums\Generations;

/**
 * The image sizes users can choose. The values are the ones Pixelforge expects.
 */
enum ImageSize: string
{
    case Square = '1024x1024';
    case Landscape = '1536x1024';
    case Portrait = '1024x1536';

    /**
     * The name to show users in the dropdown.
     */
    public function label(): string
    {
        return match ($this) {
            self::Square => 'Square',
            self::Landscape => 'Landscape',
            self::Portrait => 'Portrait',
        };
    }
}
