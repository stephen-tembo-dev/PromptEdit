<?php

namespace App\Support\Exceptions\Pixelforge;

/**
 * We got no answer from Pixelforge: we couldn't connect, or it timed out.
 * The request may still have reached it.
 */
class PixelforgeConnectionException extends PixelforgeException {}
