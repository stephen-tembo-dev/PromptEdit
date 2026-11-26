<?php

namespace App\Enums\Generations;

/**
 * The image qualities users can choose. The values are the ones Pixelforge expects.
 */
enum ImageQuality: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    /**
     * The name to show users in the dropdown.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }
}
