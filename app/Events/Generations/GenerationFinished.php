<?php

namespace App\Events\Generations;

use App\Models\Generations\Generation;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A picture has just succeeded or failed, so its Pixelforge slot is free.
 *
 * It's only sent once the database transaction has been saved, so listeners
 * never see a status that could still be rolled back.
 */
class GenerationFinished implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Generation $generation) {}
}
