<?php

namespace Tests\Feature\Jobs\Generations;

use App\Jobs\Generations\DispatchWaitingGenerations;
use App\Jobs\Generations\SubmitGeneration;
use App\Models\Generations\Generation;
use App\Services\Generations\GenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchWaitingGenerationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_submission_is_queued_per_free_slot(): void
    {
        Queue::fake();
        Generation::factory()->processing()->count(2)->create();
        $oldest = Generation::factory()->waitingForSlot()->create(['created_at' => now()->subMinutes(2)]);
        Generation::factory()->waitingForSlot()->create(['created_at' => now()->subMinute()]);

        (new DispatchWaitingGenerations)->handle(app(GenerationService::class));

        Queue::assertPushed(SubmitGeneration::class, 1);
        Queue::assertPushed(SubmitGeneration::class, fn (SubmitGeneration $job): bool => $job->generation->is($oldest));
    }
}
