<?php

namespace App\Jobs\Generations;

use App\Services\Generations\GenerationService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Fills any free Pixelforge slots with the oldest waiting pictures, and starts
 * a job to send each one.
 *
 * It runs when a slot might have freed up (a picture was created or finished)
 * and every minute just in case. If it's started many times at once, only one
 * copy actually runs.
 */
class DispatchWaitingGenerations implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    /**
     * Claim the free slots and queue a SubmitGeneration job for each picture.
     */
    public function handle(GenerationService $generations): void
    {
        foreach ($generations->claimFreeSlots() as $generation) {
            SubmitGeneration::dispatch($generation);
        }
    }
}
