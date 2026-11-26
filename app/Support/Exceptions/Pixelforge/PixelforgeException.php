<?php

namespace App\Support\Exceptions\Pixelforge;

use RuntimeException;

/**
 * Anything that went wrong talking to Pixelforge. Catch this when it doesn't
 * matter whether Pixelforge said no or never answered.
 */
abstract class PixelforgeException extends RuntimeException {}
