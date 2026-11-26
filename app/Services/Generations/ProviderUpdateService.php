<?php

namespace App\Services\Generations;

use App\Enums\Generations\GenerationStatus;
use App\Events\Generations\GenerationFinished;
use App\Events\Generations\GenerationUpdated;
use App\Models\Generations\Generation;
use App\Repositories\Contracts\Billing\RateCardRepositoryInterface;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use App\Services\Billing\BalanceService;
use App\Support\Data\Pixelforge\GenerationSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The one place where news from Pixelforge updates a picture, whether it came
 * from the reply when we sent it, a webhook, or our own polling.
 *
 * - We find the picture by our own uuid, so a webhook that arrives before
 *   Pixelforge's reply still finds it.
 * - The status only moves forward. A late "processing" after "succeeded" is
 *   ignored, and a finished picture never changes again.
 * - When a picture finishes, the money is settled, and only once.
 */
class ProviderUpdateService
{
    public function __construct(
        private GenerationRepositoryInterface $generations,
        private RateCardRepositoryInterface $rateCards,
        private BalanceService $balance,
    ) {}

    /**
     * Apply Pixelforge's latest news to our picture.
     * Returns the updated picture, or null if it isn't one of ours.
     */
    public function apply(GenerationSnapshot $snapshot): ?Generation
    {
        if ($snapshot->generationUuid === null) {
            return null;
        }

        $generation = DB::transaction(function () use ($snapshot): ?Generation {
            $generation = $this->generations->lockByUuid($snapshot->generationUuid);

            if ($generation === null) {
                return null;
            }

            $this->warnIfOutcomeDiffers($generation, $snapshot);

            $changes = $this->identityChanges($generation, $snapshot);

            if ($generation->status->canTransitionTo($snapshot->status)) {
                $changes = [...$changes, ...$this->statusChanges($generation, $snapshot)];
            }

            if ($changes !== []) {
                $this->generations->update($generation, $changes);
            }

            if ($generation->wasChanged('status')) {
                if ($generation->isFinished()) {
                    $this->balance->settle($generation);

                    GenerationFinished::dispatch($generation);
                }

                GenerationUpdated::dispatch($generation);
            }

            return $generation;
        });

        if ($generation === null) {
            Log::info('Ignored a Pixelforge update for an unknown generation.', [
                'provider_id' => $snapshot->providerId,
                'generation_uuid' => $snapshot->generationUuid,
            ]);
        }

        return $generation;
    }

    /**
     * Anything we might not have yet: Pixelforge's id for the picture, and the
     * prices Pixelforge fixed for it.
     *
     * @return array<string, mixed>
     */
    private function identityChanges(Generation $generation, GenerationSnapshot $snapshot): array
    {
        $changes = [];

        if ($generation->provider_id === null && $snapshot->providerId !== null) {
            $changes['provider_id'] = $snapshot->providerId;
        }

        if ($generation->submitted_at === null) {
            $changes['submitted_at'] = now();
        }

        if ($generation->rate_card_id === null && $snapshot->rates !== null) {
            $changes['rate_card_id'] = $this->rateCards->findOrCreate(
                $snapshot->rates->inputTextNanoPerMillion,
                $snapshot->rates->inputImageNanoPerMillion,
                $snapshot->rates->outputNanoPerMillion,
                $snapshot->rates->reasoningNanoPerMillion,
            )->getKey();
        }

        return $changes;
    }

    /**
     * The picture's new status. On success, the token counts and the image
     * link come with it. On failure, the error does.
     *
     * @return array<string, mixed>
     */
    private function statusChanges(Generation $generation, GenerationSnapshot $snapshot): array
    {
        $changes = [
            'status' => $snapshot->status,
            'started_at' => $snapshot->startedAt ?? $generation->started_at,
        ];

        if ($snapshot->status === GenerationStatus::Succeeded && $snapshot->usage !== null) {
            $changes += [
                'input_text_tokens' => $snapshot->usage->inputTextTokens,
                'input_image_tokens' => $snapshot->usage->inputImageTokens,
                'output_tokens' => $snapshot->usage->outputTokens,
                'reasoning_tokens' => $snapshot->usage->reasoningTokens,
                'image_url' => $snapshot->imageUrl,
                'image_url_expires_at' => $snapshot->imageUrlExpiresAt,
            ];
        }

        if ($snapshot->status === GenerationStatus::Failed) {
            $changes += [
                'error_code' => $snapshot->errorCode,
                'error_message' => $snapshot->errorMessage,
            ];
        }

        if ($snapshot->status->isFinal()) {
            $changes['completed_at'] = $snapshot->completedAt ?? now();
        }

        return $changes;
    }

    /**
     * We never change a finished picture, but if Pixelforge says it ended
     * differently (we failed it, Pixelforge made it), someone needs to know:
     * the money no longer matches what Pixelforge charged us.
     */
    private function warnIfOutcomeDiffers(Generation $generation, GenerationSnapshot $snapshot): void
    {
        if (! $generation->isFinished() || ! $snapshot->status->isFinal() || $generation->status === $snapshot->status) {
            return;
        }

        Log::warning('Pixelforge reports a different outcome for a generation we already finished.', [
            'generation' => $generation->uuid,
            'provider_id' => $snapshot->providerId,
            'our_status' => $generation->status->value,
            'pixelforge_status' => $snapshot->status->value,
        ]);
    }
}
