<?php

namespace Tests\Feature\Jobs\Generations;

use App\Enums\Generations\GenerationStatus;
use App\Jobs\Generations\DispatchWaitingGenerations;
use App\Jobs\Generations\SubmitGeneration;
use App\Models\Generations\Generation;
use App\Services\Generations\GenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class SubmitGenerationTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_a_busy_pixelforge_puts_the_job_back_for_the_retry_after_delay(): void
    {
        $generation = Generation::factory()->waitingForSlot()->create(['submitted_at' => now()]);
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::response(
            $this->errorBody('rate_limit_error', 'concurrency_limit_exceeded', 'Too many generations in progress.'),
            429,
            ['Retry-After' => '5'],
        )]);

        $job = (new SubmitGeneration($generation))->withFakeQueueInteractions();
        $job->handle(app(GenerationService::class));

        $job->assertReleased(delay: 5);
        Queue::assertNotPushed(DispatchWaitingGenerations::class);
    }

    public function test_a_rejected_generation_frees_its_slot_for_the_next_one(): void
    {
        $generation = Generation::factory()->waitingForSlot()->create(['submitted_at' => now()]);
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::response(
            $this->errorBody('invalid_request_error', 'content_policy_violation', 'Prompt rejected.'),
            400,
        )]);

        $job = (new SubmitGeneration($generation))->withFakeQueueInteractions();
        $job->handle(app(GenerationService::class));

        $job->assertNotReleased();
        $this->assertSame(GenerationStatus::Failed, $generation->fresh()->status);
        Queue::assertPushed(DispatchWaitingGenerations::class);
    }

    public function test_an_accepted_generation_keeps_its_slot(): void
    {
        $generation = Generation::factory()->waitingForSlot()->create(['submitted_at' => now()]);
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::response($this->generationObject($generation, 'queued'), 202)]);

        $job = (new SubmitGeneration($generation))->withFakeQueueInteractions();
        $job->handle(app(GenerationService::class));

        $job->assertNotReleased();
        $this->assertNotNull($generation->fresh()->provider_id);
        Queue::assertNotPushed(DispatchWaitingGenerations::class);
    }
}
