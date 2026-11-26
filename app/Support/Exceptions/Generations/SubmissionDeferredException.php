<?php

namespace App\Support\Exceptions\Generations;

use RuntimeException;

/**
 * Thrown when Pixelforge is busy (too many pictures, too many requests,
 * overloaded, or the connection failed). It tells the job to try the same
 * picture again later, after retryAfterSeconds.
 */
class SubmissionDeferredException extends RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds,
        string $reason,
    ) {
        parent::__construct($reason);
    }
}
