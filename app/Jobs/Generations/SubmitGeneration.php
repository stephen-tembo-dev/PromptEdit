<?php

namespace App\Jobs\Generations;

use App\Models\Generations\Generation;
use App\Services\Generations\GenerationService;
use App\Support\Exceptions\Generations\SubmissionDeferredException;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends one picture to Pixelforge.
 *
 * If Pixelforge is busy, the job goes back on the queue and tries again later,
 * so it doesn't keep a worker waiting. If it gives up completely, our polling
 * will send the picture again later with the same Idempotency-Key.
 */
class SubmitGeneration implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** How long to keep trying while Pixelforge is busy. */
    private const RETRY_FOR_MINUTES = 30;

    public int $maxExceptions = 3;

    public int $uniqueFor = 1800;

    public function __construct(public Generation $generation) {}

    /**
     * Only one job per picture can be on the queue at a time.
     */
    public function uniqueId(): string
    {
        return $this->generation->uuid;
    }

    /**
     * Stop retrying after this time.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(self::RETRY_FOR_MINUTES);
    }

    /**
     * Send the picture. If Pixelforge is busy, put the job back on the queue
     * for the wait time we worked out.
     */
    public function handle(GenerationService $generations): void
    {
        try {
            $generations->submit($this->generation);
        } catch (SubmissionDeferredException $exception) {
            $this->release($exception->retryAfterSeconds);
        }
    }

    /**
     * Runs when the job gives up for good. We just log it, because polling
     * will pick the picture up again.
     */
    public function failed(?Throwable $exception): void
    {
        Log::warning('Submitting a generation to Pixelforge gave up; polling will resend it.', [
            'generation' => $this->generation->uuid,
            'error' => $exception?->getMessage(),
        ]);
    }
}
