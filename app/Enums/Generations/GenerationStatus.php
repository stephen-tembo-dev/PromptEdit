<?php

namespace App\Enums\Generations;

/**
 * The stages a picture goes through. They match Pixelforge's statuses.
 *
 * A picture only moves forward: queued, then processing, then succeeded or
 * failed. Once it has succeeded or failed it never changes again, so a late
 * or repeated message can't send it backwards.
 */
enum GenerationStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    /**
     * Where the status sits in the order. Succeeded and failed are both last.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Queued => 0,
            self::Processing => 1,
            self::Succeeded, self::Failed => 2,
        };
    }

    /**
     * True if the picture has succeeded or failed.
     */
    public function isFinal(): bool
    {
        return $this === self::Succeeded || $this === self::Failed;
    }

    /**
     * True if the picture is allowed to move from this status to the next one.
     * It must not be finished already, and the next status must be further along.
     */
    public function canTransitionTo(self $next): bool
    {
        return ! $this->isFinal() && $next->rank() > $this->rank();
    }
}
