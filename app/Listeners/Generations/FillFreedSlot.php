<?php

namespace App\Listeners\Generations;

use App\Events\Generations\GenerationFinished;
use App\Jobs\Generations\DispatchWaitingGenerations;

/**
 * Sends the next waiting picture as soon as one finishes, instead of waiting
 * for the scheduler's next run.
 */
class FillFreedSlot
{
    public function handle(GenerationFinished $event): void
    {
        DispatchWaitingGenerations::dispatch();
    }
}
