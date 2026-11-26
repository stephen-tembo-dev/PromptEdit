<?php

namespace Tests\Feature\Repositories\Generations;

use App\Models\Generations\Generation;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * Uses DatabaseMigrations rather than RefreshDatabase: RefreshDatabase wraps each
 * test in a transaction, which would hide a missing one from the guard.
 */
class GenerationLockingTest extends TestCase
{
    use DatabaseMigrations;

    public function test_locking_a_generation_requires_a_transaction(): void
    {
        $generations = app(GenerationRepositoryInterface::class);
        $generation = Generation::factory()->processing()->create();

        $locked = DB::transaction(fn () => $generations->lockByUuid($generation->uuid));
        $this->assertTrue($generation->is($locked));

        $this->expectException(LogicException::class);

        $generations->lockByUuid($generation->uuid);
    }
}
