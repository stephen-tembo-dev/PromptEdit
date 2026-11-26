<?php

namespace App\Jobs\Generations;

use App\Services\Generations\GenerationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs every minute to check on pictures we haven't heard about for a while
 * (in case their webhooks never came). Only one copy runs at a time.
 */
class SyncWithProvider implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $uniqueFor = 300;

    /**
     * Check the late pictures.
     */
    public function handle(GenerationService $generations): void
    {
        $generations->syncOverdue();
    }
}
