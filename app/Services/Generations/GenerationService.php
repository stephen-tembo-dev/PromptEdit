<?php

namespace App\Services\Generations;

use App\Enums\Generations\GenerationStatus;
use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Events\Generations\GenerationFinished;
use App\Events\Generations\GenerationUpdated;
use App\Models\Generations\Generation;
use App\Models\User\User;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use App\Services\Billing\BalanceService;
use App\Services\Billing\PricingService;
use App\Services\Pixelforge\PixelforgeService;
use App\Support\Exceptions\Billing\InsufficientCreditsException;
use App\Support\Exceptions\Generations\SubmissionDeferredException;
use App\Support\Exceptions\Pixelforge\PixelforgeConnectionException;
use App\Support\Exceptions\Pixelforge\PixelforgeRequestException;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Takes a picture request from the moment the user clicks Generate until
 * Pixelforge has it: save it and take the deposit, wait for a free slot,
 * send it, and chase it up if we never hear back.
 */
class GenerationService
{
    /**
     * How many "overloaded" answers we accept before giving up on a picture.
     * We only give up if Pixelforge answered every try clearly.
     */
    public const MAX_OVERLOAD_ATTEMPTS = 8;

    /**
     * A picture normally takes 5 to 30 seconds (60 to 90 with [slow]).
     * If we still have no news after this long, we ask Pixelforge directly.
     */
    public const OVERDUE_AFTER_SECONDS = 120;

    /**
     * Pixelforge retries a webhook for about half an hour. If a picture it
     * might have made still hasn't been heard of after this long, it was
     * never made, and we can refund it.
     */
    public const MAY_EXIST_WAIT_MINUTES = 60;

    private const MAX_BACKOFF_SECONDS = 60;

    private const SLOT_LOCK_KEY = 'pixelforge:slot-allocation';

    /** Longer than one request can take (5s to connect plus 15s to answer). */
    private const SUBMIT_LOCK_SECONDS = 30;

    public function __construct(
        private GenerationRepositoryInterface $generations,
        private PricingService $pricing,
        private BalanceService $balance,
        private PixelforgeService $pixelforge,
        private ProviderUpdateService $providerUpdates,
        #[Config('services.pixelforge.concurrency_limit')] private int $concurrencyLimit,
    ) {}

    /**
     * Save a new picture request and take the deposit from the user's balance.
     *
     * The user's row is locked until the deposit is written, so two tabs
     * clicking Generate at the same time can't spend the same money.
     *
     * @param  list<string>  $referenceImageUrls
     *
     * @throws InsufficientCreditsException
     */
    public function create(User $user, string $prompt, ImageSize $size, ImageQuality $quality, array $referenceImageUrls): Generation
    {
        $quote = $this->pricing->quote($prompt, $size, $quality, count($referenceImageUrls));

        return DB::transaction(function () use ($user, $prompt, $size, $quality, $referenceImageUrls, $quote): Generation {
            $this->balance->lockAndEnsureCanAfford($user, $quote->holdChargeNano);

            $generation = $this->generations->create(
                $user,
                $prompt,
                $size,
                $quality,
                $referenceImageUrls,
                $quote->estimatedChargeNano,
            );

            $this->balance->hold($generation, $quote->holdChargeNano);

            return $generation;
        });
    }

    /**
     * Pick the next waiting pictures to send, oldest first.
     *
     * Pixelforge only lets us run 3 at a time across all users, so we only take
     * as many as there are free slots. The lock stops two workers grabbing the
     * same slot.
     *
     * @return Collection<int, Generation>
     */
    public function claimFreeSlots(): Collection
    {
        return Cache::lock(self::SLOT_LOCK_KEY, 10)->block(5, function (): Collection {
            $freeSlots = $this->concurrencyLimit - $this->generations->countInFlight();

            if ($freeSlots <= 0) {
                return new Collection;
            }

            $claimed = $this->generations->waitingForSlot($freeSlots);

            foreach ($claimed as $generation) {
                $this->generations->markClaimed($generation);

                GenerationUpdated::dispatch($generation);
            }

            return $claimed;
        });
    }

    /**
     * Send a picture to Pixelforge.
     *
     * Our uuid goes along as the Idempotency-Key, so sending the same picture
     * twice is safe. If Pixelforge is busy we throw SubmissionDeferredException
     * so the job tries again later. If Pixelforge says no for good, the picture
     * fails and the user gets their deposit back.
     *
     * Only one worker sends a given picture at a time. Pixelforge doesn't say
     * what it does with two requests for the same key at once, and we'd rather
     * not find out.
     *
     * @throws SubmissionDeferredException
     */
    public function submit(Generation $generation): void
    {
        $lock = Cache::lock('pixelforge:submit:'.$generation->uuid, self::SUBMIT_LOCK_SECONDS);

        if (! $lock->get()) {
            throw new SubmissionDeferredException(5, 'Another worker is sending this generation right now.');
        }

        try {
            // The other worker may have just finished with it.
            $generation->refresh();

            if ($generation->isFinished() || $generation->provider_id !== null) {
                return;
            }

            $this->send($generation);
        } finally {
            $lock->release();
        }
    }

    /**
     * Mark a picture as failed and give the whole deposit back.
     * Does nothing if the picture has already finished.
     */
    public function fail(Generation $generation, string $errorCode, string $errorMessage): void
    {
        DB::transaction(function () use ($generation, $errorCode, $errorMessage): void {
            $locked = $this->generations->lockByUuid($generation->uuid);

            if ($locked === null || $locked->isFinished()) {
                return;
            }

            $this->generations->markFailed($locked, $errorCode, $errorMessage);
            $this->balance->settle($locked);

            GenerationFinished::dispatch($locked);
            GenerationUpdated::dispatch($locked);
        });

        $generation->refresh();
    }

    /**
     * Backup plan for when webhooks don't arrive.
     *
     * Finds pictures we've heard nothing about for a while and asks Pixelforge
     * what happened to them. If nothing is late, no request is made, so we
     * don't waste our 60 requests a minute.
     */
    public function syncOverdue(): void
    {
        $cutoff = now()->subSeconds(self::OVERDUE_AFTER_SECONDS);
        $overdue = $this->generations->inFlight()
            ->filter(fn (Generation $generation): bool => $this->isOverdue($generation, $cutoff))
            ->values();

        if ($overdue->isEmpty()) {
            return;
        }

        // With one late picture, one direct lookup is cheapest. With more, it's
        // cheaper to fetch Pixelforge's lists and match them up.
        $listed = $overdue->count() > 1 ? $this->applyListedGenerations() : [];

        foreach ($overdue as $generation) {
            if (isset($listed[$generation->uuid])) {
                continue;
            }

            try {
                $this->catchUp($generation);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Make one try at sending the picture and deal with the answer.
     *
     * @throws SubmissionDeferredException
     */
    private function send(Generation $generation): void
    {
        $this->generations->recordSubmitAttempt($generation);

        try {
            $snapshot = $this->pixelforge->createGeneration($generation);
        } catch (PixelforgeConnectionException $exception) {
            $this->markMayExistAtProvider($generation);

            throw $this->defer($generation, null, 'connection_failed', $exception->getMessage());
        } catch (PixelforgeRequestException $exception) {
            $this->handleRejection($generation, $exception);

            return;
        }

        $this->providerUpdates->apply($snapshot);
    }

    /**
     * Decide what to do when Pixelforge refuses a picture: wait and retry if
     * it's busy, otherwise fail the picture.
     *
     * @throws SubmissionDeferredException
     */
    private function handleRejection(Generation $generation, PixelforgeRequestException $exception): void
    {
        if (in_array($exception->errorCode, ['concurrency_limit_exceeded', 'rate_limit_exceeded'], true)) {
            throw $this->defer($generation, $exception->retryAfterSeconds ?? 5, $exception->errorCode, $exception->getMessage(), $exception->status);
        }

        if ($exception->errorCode === 'overloaded') {
            $this->generations->recordOverload($generation);

            if ($generation->overload_attempts >= self::MAX_OVERLOAD_ATTEMPTS && ! $generation->may_exist_at_provider) {
                $this->fail($generation, 'overloaded', 'Pixelforge is overloaded right now. Please try again in a few minutes.');

                return;
            }

            throw $this->defer($generation, $exception->retryAfterSeconds ?? 2, $exception->errorCode, $exception->getMessage(), $exception->status);
        }

        if ($exception->isServerError()) {
            $this->markMayExistAtProvider($generation);

            throw $this->defer($generation, $exception->retryAfterSeconds, $exception->errorCode, $exception->getMessage(), $exception->status);
        }

        // An earlier try went unanswered, so Pixelforge may have made the
        // picture and will charge us for it. Resending the same key should
        // just hand it back, but if we get a refusal instead (a revoked API
        // key, say), we keep waiting for its webhook rather than refund
        // something we've paid for.
        if ($generation->may_exist_at_provider && ! $this->hasWaitedOutWebhooks($generation)) {
            throw $this->defer($generation, $exception->retryAfterSeconds, $exception->errorCode, $exception->getMessage(), $exception->status);
        }

        $this->fail($generation, $exception->errorCode, $this->messageFor($exception));
    }

    /**
     * Remember that Pixelforge might have made this picture even though we
     * didn't get a clear answer (the connection dropped, or a 5xx error).
     *
     * Once this is set, we don't fail the picture ourselves until Pixelforge
     * has had time to tell us about it. If we did, we'd give the deposit back
     * while Pixelforge still charges us for it.
     */
    private function markMayExistAtProvider(Generation $generation): void
    {
        if (! $generation->may_exist_at_provider) {
            $this->generations->markMayExistAtProvider($generation);
        }
    }

    /**
     * True once a picture has been waiting long enough that any webhook for it
     * would have reached us by now.
     */
    private function hasWaitedOutWebhooks(Generation $generation): bool
    {
        return $generation->submitted_at === null
            || $generation->submitted_at->lt(now()->subMinutes(self::MAY_EXIST_WAIT_MINUTES));
    }

    /**
     * True if we should ask Pixelforge about this picture.
     *
     * A picture Pixelforge hasn't accepted yet, but that was tried in the last
     * couple of minutes, still has its SubmitGeneration job working on it, so
     * we leave it alone.
     */
    private function isOverdue(Generation $generation, CarbonInterface $cutoff): bool
    {
        if ($generation->submitted_at->gte($cutoff)) {
            return false;
        }

        return $generation->provider_id !== null
            || $generation->last_submit_attempt_at === null
            || $generation->last_submit_attempt_at->lt($cutoff);
    }

    /**
     * Work out how long to wait before trying again, and log why.
     *
     * The wait doubles with each try (up to a minute), but is never shorter
     * than what Pixelforge asked for in Retry-After.
     */
    private function defer(
        Generation $generation,
        ?int $retryAfterSeconds,
        string $cause,
        string $reason,
        ?int $status = null,
    ): SubmissionDeferredException {
        $backoff = min(self::MAX_BACKOFF_SECONDS, 2 ** max(0, $generation->submit_attempts - 1));
        $delay = max($retryAfterSeconds ?? 0, $backoff);

        Log::info('Pixelforge did not accept a generation yet; it will be retried.', [
            'generation' => $generation->uuid,
            'attempt' => $generation->submit_attempts,
            'cause' => $cause,
            'status' => $status,
            'message' => $reason,
            'retry_in_seconds' => $delay,
        ]);

        return new SubmissionDeferredException($delay, $reason);
    }

    /**
     * A friendlier error message for the user, where we have one.
     */
    private function messageFor(PixelforgeRequestException $exception): string
    {
        return match ($exception->errorCode) {
            'content_policy_violation' => 'Your prompt was rejected by the content policy. You have not been charged.',
            'daily_limit_exceeded' => 'The daily generation limit has been reached. Please try again after midnight UTC.',
            default => $exception->getMessage(),
        };
    }

    /**
     * Fetch Pixelforge's lists of queued and processing pictures and update ours.
     *
     * @return array<string, true> the uuids of our pictures that were in the lists
     */
    private function applyListedGenerations(): array
    {
        $listed = [];

        foreach ([GenerationStatus::Queued, GenerationStatus::Processing] as $status) {
            $cursor = null;

            do {
                $page = $this->pixelforge->listGenerations($status, $cursor);

                foreach ($page->generations as $snapshot) {
                    if ($this->providerUpdates->apply($snapshot) !== null) {
                        $listed[$snapshot->generationUuid] = true;
                    }
                }

                $cursor = $page->nextCursor;
            } while ($page->hasMore && $cursor !== null);
        }

        return $listed;
    }

    /**
     * Find out what happened to one late picture.
     *
     * If we have Pixelforge's id for it, we look it up. If we don't (we never
     * got the reply), we send it again. The Idempotency-Key means Pixelforge
     * just returns the one it already has instead of making a second one.
     */
    private function catchUp(Generation $generation): void
    {
        if ($generation->provider_id !== null) {
            $this->providerUpdates->apply($this->pixelforge->getGeneration($generation->provider_id));

            return;
        }

        try {
            $this->submit($generation);
        } catch (SubmissionDeferredException) {
            // Still busy. The next check will try again.
        }
    }
}
