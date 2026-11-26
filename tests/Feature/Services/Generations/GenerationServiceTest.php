<?php

namespace Tests\Feature\Services\Generations;

use App\Enums\Billing\LedgerEntryType;
use App\Enums\Generations\GenerationStatus;
use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Events\Generations\GenerationUpdated;
use App\Models\Billing\CreditPurchase;
use App\Models\Generations\Generation;
use App\Models\Generations\GenerationReferenceImage;
use App\Models\User\User;
use App\Services\Billing\BalanceService;
use App\Services\Generations\GenerationService;
use App\Support\Exceptions\Billing\InsufficientCreditsException;
use App\Support\Exceptions\Generations\SubmissionDeferredException;
use Database\Factories\Generations\GenerationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class GenerationServiceTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    private const HOLD_NANO = 90_000_000;

    private const CREDITS_NANO = 1_000_000_000;

    // create()

    public function test_a_new_generation_records_its_estimate_and_holds_its_maximum_plus_the_margin(): void
    {
        $this->fakePricing();
        $user = User::factory()->create();
        CreditPurchase::factory()->for($user)->create(['amount_nano' => self::CREDITS_NANO]);

        $generation = app(GenerationService::class)->create(
            $user,
            str_repeat('a', 400),
            ImageSize::Landscape,
            ImageQuality::High,
            ['https://example.com/a.png', 'https://example.com/b.png'],
        );

        $this->assertTrue($generation->isWaitingForSlot());
        $this->assertSame(467_406_250, $generation->ledgerEntries()->where('type', LedgerEntryType::Hold)->value('amount_nano'));
        $this->assertLessThan(467_406_250, $generation->estimated_charge_nano);
        $this->assertSame(self::CREDITS_NANO - 467_406_250, app(BalanceService::class)->balanceNano($user));
    }

    public function test_a_user_who_cannot_afford_the_hold_gets_nothing_created(): void
    {
        $this->fakePricing();
        $user = User::factory()->create();
        CreditPurchase::factory()->for($user)->create(['amount_nano' => 467_406_249]);

        try {
            app(GenerationService::class)->create($user, str_repeat('a', 400), ImageSize::Landscape, ImageQuality::High, ['https://example.com/a.png', 'https://example.com/b.png']);
            $this->fail('Expected the generation to be blocked.');
        } catch (InsufficientCreditsException $exception) {
            $this->assertSame(467_406_250, $exception->requiredNano);
        }

        $this->assertSame(0, Generation::count());
    }

    public function test_a_second_submission_cannot_spend_credits_already_held(): void
    {
        $this->fakePricing();
        $user = User::factory()->create();
        CreditPurchase::factory()->for($user)->create(['amount_nano' => 600_000_000]);
        $service = app(GenerationService::class);

        $service->create($user, str_repeat('a', 400), ImageSize::Landscape, ImageQuality::High, ['https://example.com/a.png', 'https://example.com/b.png']);

        $this->expectException(InsufficientCreditsException::class);

        $service->create($user, str_repeat('a', 400), ImageSize::Landscape, ImageQuality::High, ['https://example.com/a.png', 'https://example.com/b.png']);
    }

    // fail()

    public function test_failing_a_generation_releases_its_hold_and_happens_once(): void
    {
        $this->fakePricing();
        $user = User::factory()->create();
        CreditPurchase::factory()->for($user)->create(['amount_nano' => self::CREDITS_NANO]);
        $service = app(GenerationService::class);
        $generation = $service->create($user, 'A lighthouse [blocked]', ImageSize::Square, ImageQuality::Low, []);

        $service->fail($generation, 'content_policy_violation', 'Rejected.');
        $service->fail($generation, 'something_else', 'Ignored.');

        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('content_policy_violation', $generation->error_code);
        $this->assertSame(self::CREDITS_NANO, app(BalanceService::class)->balanceNano($user));
    }

    // claimFreeSlots()

    public function test_only_free_slots_are_handed_out_oldest_request_first(): void
    {
        Generation::factory()->processing()->create();
        $first = Generation::factory()->waitingForSlot()->create(['created_at' => now()->subMinutes(4)]);
        $second = Generation::factory()->waitingForSlot()->create(['created_at' => now()->subMinutes(3)]);
        $third = Generation::factory()->waitingForSlot()->create(['created_at' => now()->subMinutes(2)]);

        $claimed = app(GenerationService::class)->claimFreeSlots();

        $this->assertSame([$first->id, $second->id], $claimed->pluck('id')->all());
        $this->assertNotNull($first->fresh()->submitted_at);
        $this->assertTrue($third->fresh()->isWaitingForSlot());
    }

    public function test_the_owner_is_told_when_their_picture_gets_a_slot(): void
    {
        Event::fake([GenerationUpdated::class]);
        $waiting = Generation::factory()->waitingForSlot()->create();

        app(GenerationService::class)->claimFreeSlots();

        Event::assertDispatched(GenerationUpdated::class, fn (GenerationUpdated $event): bool => $event->generation->is($waiting));
    }

    public function test_nothing_is_handed_out_while_all_three_slots_are_busy(): void
    {
        Generation::factory()->queued()->create();
        Generation::factory()->processing()->count(2)->create();
        $waiting = Generation::factory()->waitingForSlot()->create();

        $this->assertCount(0, app(GenerationService::class)->claimFreeSlots());
        $this->assertTrue($waiting->fresh()->isWaitingForSlot());
    }

    // submit()

    public function test_an_accepted_generation_saves_its_gen_id_and_locked_rates(): void
    {
        $generation = $this->claimedGeneration();
        GenerationReferenceImage::factory()->for($generation)->create(['position' => 0, 'url' => 'https://example.com/style.png']);
        $object = $this->generationObject($generation, 'queued');
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::response($object, 202)]);

        app(GenerationService::class)->submit($generation);

        $generation->refresh();
        $this->assertSame($object['id'], $generation->provider_id);
        $this->assertSame(GenerationStatus::Queued, $generation->status);
        $this->assertSame(12_500_000_000, $generation->rateCard->reasoning_nano_per_million);

        Http::assertSent(fn (Request $request): bool => $request->header('Idempotency-Key') === [$generation->uuid]
            && $request['metadata'] === ['generation_id' => $generation->uuid]
            && $request['reference_images'] === ['https://example.com/style.png']
            && $request['size'] === $generation->size->value);
    }

    public function test_a_generation_already_accepted_is_not_sent_again(): void
    {
        Http::fake();
        $generation = Generation::factory()->queued()->create();

        app(GenerationService::class)->submit($generation);

        Http::assertNothingSent();
    }

    public function test_the_concurrency_limit_defers_the_submission_for_at_least_retry_after_and_logs_why(): void
    {
        Log::spy();
        $generation = $this->claimedGeneration();
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::response(
            $this->errorBody('rate_limit_error', 'concurrency_limit_exceeded', 'Too many generations in progress.'),
            429,
            ['Retry-After' => '5'],
        )]);

        try {
            app(GenerationService::class)->submit($generation);
            $this->fail('Expected the submission to be deferred.');
        } catch (SubmissionDeferredException $exception) {
            $this->assertGreaterThanOrEqual(5, $exception->retryAfterSeconds);
        }

        $this->assertSame(GenerationStatus::Queued, $generation->fresh()->status);
        $this->assertSame(self::CREDITS_NANO - self::HOLD_NANO, app(BalanceService::class)->balanceNano($generation->user));
        Log::shouldHaveReceived('info')->once()->withArgs(fn (string $message, array $context): bool => $context['generation'] === $generation->uuid
            && $context['attempt'] === 1
            && $context['cause'] === 'concurrency_limit_exceeded'
            && $context['status'] === 429
            && $context['retry_in_seconds'] >= 5);
    }

    public function test_an_overloaded_pixelforge_is_retried_and_then_given_up_on_for_free(): void
    {
        $generation = $this->claimedGeneration();
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::response(
            $this->errorBody('api_error', 'overloaded', 'Pixelforge is overloaded.'),
            503,
            ['Retry-After' => '2'],
        )]);
        $service = app(GenerationService::class);

        for ($attempt = 1; $attempt < GenerationService::MAX_OVERLOAD_ATTEMPTS; $attempt++) {
            try {
                $service->submit($generation);
                $this->fail("Attempt {$attempt} should have been deferred.");
            } catch (SubmissionDeferredException $exception) {
                $this->assertGreaterThanOrEqual(2, $exception->retryAfterSeconds);
            }
        }

        $service->submit($generation);

        $this->assertSame(GenerationStatus::Failed, $generation->fresh()->status);
        $this->assertSame(self::CREDITS_NANO, app(BalanceService::class)->balanceNano($generation->user));
    }

    public function test_an_overloaded_pixelforge_is_never_given_up_on_after_an_unanswered_attempt(): void
    {
        $generation = $this->claimedGeneration();
        $overloaded = Http::sequence()->pushFailedConnection();

        for ($attempt = 1; $attempt <= GenerationService::MAX_OVERLOAD_ATTEMPTS; $attempt++) {
            $overloaded->push($this->errorBody('api_error', 'overloaded', 'Pixelforge is overloaded.'), 503, ['Retry-After' => '2']);
        }

        Http::fake([self::PIXELFORGE_URL.'/generations' => $overloaded]);
        $service = app(GenerationService::class);

        for ($attempt = 0; $attempt <= GenerationService::MAX_OVERLOAD_ATTEMPTS; $attempt++) {
            try {
                $service->submit($generation);
                $this->fail("Attempt {$attempt} should have been deferred.");
            } catch (SubmissionDeferredException) {
                // Pixelforge may have created it on the unanswered attempt, so keep asking.
            }
        }

        $this->assertSame(GenerationStatus::Queued, $generation->fresh()->status);
        $this->assertSame(self::CREDITS_NANO - self::HOLD_NANO, app(BalanceService::class)->balanceNano($generation->user));
    }

    public function test_only_overloaded_answers_count_towards_giving_up(): void
    {
        $generation = $this->claimedGeneration();
        $answers = Http::sequence()->push(
            $this->errorBody('rate_limit_error', 'rate_limit_exceeded', 'Too many requests.'),
            429,
            ['Retry-After' => '1'],
        );

        for ($attempt = 1; $attempt < GenerationService::MAX_OVERLOAD_ATTEMPTS; $attempt++) {
            $answers->push($this->errorBody('api_error', 'overloaded', 'Pixelforge is overloaded.'), 503, ['Retry-After' => '2']);
        }

        Http::fake([self::PIXELFORGE_URL.'/generations' => $answers]);
        $service = app(GenerationService::class);

        for ($attempt = 1; $attempt <= GenerationService::MAX_OVERLOAD_ATTEMPTS; $attempt++) {
            try {
                $service->submit($generation);
                $this->fail("Attempt {$attempt} should have been deferred.");
            } catch (SubmissionDeferredException) {
                // One rate limit and seven overloads: still short of the overload limit.
            }
        }

        $this->assertSame(GenerationStatus::Queued, $generation->fresh()->status);
        $this->assertSame(GenerationService::MAX_OVERLOAD_ATTEMPTS - 1, $generation->fresh()->overload_attempts);
    }

    public function test_a_blocked_prompt_fails_cleanly_and_costs_nothing(): void
    {
        $generation = $this->claimedGeneration();
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::response(
            $this->errorBody('invalid_request_error', 'content_policy_violation', 'Prompt rejected.'),
            400,
        )]);

        app(GenerationService::class)->submit($generation);

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed, $generation->status);
        $this->assertSame('content_policy_violation', $generation->error_code);
        $this->assertStringContainsString('content policy', $generation->error_message);
        $this->assertSame(self::CREDITS_NANO, app(BalanceService::class)->balanceNano($generation->user));
    }

    public function test_a_network_failure_is_retried_because_the_key_makes_resending_safe(): void
    {
        Log::spy();
        $generation = $this->claimedGeneration();
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::failedConnection()]);

        try {
            app(GenerationService::class)->submit($generation);
            $this->fail('Expected the submission to be deferred.');
        } catch (SubmissionDeferredException) {
            Log::shouldHaveReceived('info')->once()->withArgs(fn (string $message, array $context): bool => $context['cause'] === 'connection_failed'
                && $context['status'] === null);
        }
    }

    public function test_a_refusal_after_an_unanswered_attempt_waits_instead_of_refunding(): void
    {
        $generation = $this->claimedGeneration();
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::sequence()
            ->pushFailedConnection()
            ->push($this->errorBody('authentication_error', 'invalid_api_key', 'Invalid API key.'), 401)]);
        $service = app(GenerationService::class);

        foreach (['no answer', 'refused'] as $attempt) {
            try {
                $service->submit($generation);
                $this->fail("The {$attempt} attempt should have been deferred.");
            } catch (SubmissionDeferredException) {
                // Pixelforge may have the picture from the first try.
            }
        }

        $this->assertSame(GenerationStatus::Queued, $generation->fresh()->status);
        $this->assertSame(self::CREDITS_NANO - self::HOLD_NANO, app(BalanceService::class)->balanceNano($generation->user));
    }

    public function test_a_refusal_is_refunded_once_any_webhook_would_have_arrived(): void
    {
        $generation = $this->claimedGeneration();
        $generation->forceFill([
            'may_exist_at_provider' => true,
            'submitted_at' => now()->subMinutes(GenerationService::MAY_EXIST_WAIT_MINUTES + 1),
        ])->save();
        Http::fake([self::PIXELFORGE_URL.'/generations' => Http::response(
            $this->errorBody('authentication_error', 'invalid_api_key', 'Invalid API key.'),
            401,
        )]);

        app(GenerationService::class)->submit($generation);

        $this->assertSame(GenerationStatus::Failed, $generation->fresh()->status);
        $this->assertSame(self::CREDITS_NANO, app(BalanceService::class)->balanceNano($generation->user));
    }

    public function test_a_generation_another_worker_is_sending_is_not_sent_again(): void
    {
        Http::fake();
        $generation = $this->claimedGeneration();
        Cache::lock('pixelforge:submit:'.$generation->uuid, 30)->get();

        $this->expectException(SubmissionDeferredException::class);

        try {
            app(GenerationService::class)->submit($generation);
        } finally {
            Http::assertNothingSent();
        }
    }

    // syncOverdue()

    public function test_polling_leaves_a_generation_alone_while_its_send_job_is_still_retrying(): void
    {
        Http::fake();
        Generation::factory()->waitingForSlot()->create([
            'submitted_at' => now()->subMinutes(5),
            'last_submit_attempt_at' => now()->subSeconds(30),
        ]);

        app(GenerationService::class)->syncOverdue();

        Http::assertNothingSent();
    }

    public function test_an_idle_app_makes_no_pixelforge_requests(): void
    {
        Http::fake();
        Generation::factory()->succeeded()->create(['submitted_at' => now()->subHour()]);
        Generation::factory()->waitingForSlot()->create();

        app(GenerationService::class)->syncOverdue();

        Http::assertNothingSent();
    }

    public function test_generations_still_within_a_normal_run_are_left_to_their_webhooks(): void
    {
        Http::fake();
        Generation::factory()->processing()->create(['submitted_at' => now()->subSeconds(90)]);

        app(GenerationService::class)->syncOverdue();

        Http::assertNothingSent();
    }

    public function test_a_single_overdue_generation_is_fetched_by_id_without_listing(): void
    {
        $overdue = $this->overdue(Generation::factory()->processing());
        Http::fake([
            self::PIXELFORGE_URL.'/generations/'.$overdue->provider_id => Http::response($this->generationObject($overdue, 'failed')),
        ]);

        app(GenerationService::class)->syncOverdue();

        $this->assertSame(GenerationStatus::Failed, $overdue->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_several_overdue_generations_are_listed_and_the_finished_ones_fetched(): void
    {
        $stillProcessing = $this->overdue(Generation::factory()->queued());
        $finishedWhileWebhooksFailed = $this->overdue(Generation::factory()->processing());

        Http::fake(function (Request $request) use ($stillProcessing, $finishedWhileWebhooksFailed) {
            return match (true) {
                str_contains($request->url(), 'status=processing') => Http::response($this->page([
                    $this->generationObject($stillProcessing, 'processing'),
                ])),
                str_contains($request->url(), 'status=queued') => Http::response($this->page([])),
                str_ends_with($request->url(), '/generations/'.$finishedWhileWebhooksFailed->provider_id) => Http::response(
                    $this->generationObject($finishedWhileWebhooksFailed, 'succeeded'),
                ),
            };
        });

        app(GenerationService::class)->syncOverdue();

        $this->assertSame(GenerationStatus::Processing, $stillProcessing->fresh()->status);
        $this->assertSame(GenerationStatus::Succeeded, $finishedWhileWebhooksFailed->fresh()->status);
        Http::assertSentCount(3);
    }

    public function test_an_overdue_generation_whose_create_response_was_lost_is_resent_with_the_same_key(): void
    {
        $lost = Generation::factory()->waitingForSlot()->create(['submitted_at' => now()->subMinutes(5)]);
        Http::fake([
            self::PIXELFORGE_URL.'/generations' => Http::response($this->generationObject($lost, 'queued'), 202, ['Idempotent-Replayed' => 'true']),
        ]);

        app(GenerationService::class)->syncOverdue();

        $this->assertNotNull($lost->fresh()->provider_id);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->header('Idempotency-Key') === [$lost->uuid]);
    }

    /**
     * A generation that has been given a slot, with credits bought and its hold placed.
     */
    private function claimedGeneration(): Generation
    {
        $generation = Generation::factory()->waitingForSlot()->create(['submitted_at' => now()]);
        CreditPurchase::factory()->for($generation->user)->create(['amount_nano' => self::CREDITS_NANO]);
        app(BalanceService::class)->hold($generation, self::HOLD_NANO);

        return $generation;
    }

    private function overdue(GenerationFactory $factory): Generation
    {
        return $factory->create(['submitted_at' => now()->subSeconds(GenerationService::OVERDUE_AFTER_SECONDS + 30)]);
    }

    /**
     * @param  list<array<string, mixed>>  $generations
     * @return array<string, mixed>
     */
    private function page(array $generations): array
    {
        return ['object' => 'list', 'data' => $generations, 'has_more' => false, 'next_cursor' => null];
    }
}
