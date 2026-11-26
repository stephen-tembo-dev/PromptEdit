<?php

namespace App\Repositories\Contracts\Generations;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Models\Generations\Generation;
use App\Models\User\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * The list of database jobs for pictures. GenerationRepository does the actual work.
 */
interface GenerationRepositoryInterface
{
    /**
     * Save a new picture request and its reference image links.
     * It starts off waiting for a free slot.
     *
     * @param  list<string>  $referenceImageUrls
     */
    public function create(
        User $user,
        string $prompt,
        ImageSize $size,
        ImageQuality $quality,
        array $referenceImageUrls,
        int $estimatedChargeNano,
    ): Generation;

    /**
     * Give a waiting picture its slot.
     */
    public function markClaimed(Generation $generation): void;

    /**
     * Count one more try at sending the picture, and remember when it was.
     */
    public function recordSubmitAttempt(Generation $generation): void;

    /**
     * Count one more "overloaded" answer from Pixelforge.
     */
    public function recordOverload(Generation $generation): void;

    /**
     * Remember that a try got no clear answer, so Pixelforge may have the picture.
     */
    public function markMayExistAtProvider(Generation $generation): void;

    /**
     * Mark the picture as failed with Pixelforge's error, or ours.
     */
    public function markFailed(Generation $generation, string $errorCode, string $errorMessage): void;

    /**
     * Save what Pixelforge told us about the picture (status, ids, tokens and so on).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Generation $generation, array $attributes): void;

    /**
     * Find a picture by our uuid.
     */
    public function findByUuid(string $uuid): ?Generation;

    /**
     * Find a picture and lock its row until the transaction ends, so a webhook
     * and our polling can't update it at the same time.
     */
    public function lockByUuid(string $uuid): ?Generation;

    /**
     * One page of the user's pictures, newest first, for the history page.
     *
     * @return LengthAwarePaginator<int, Generation>
     */
    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator;

    /**
     * The user's newest pictures, ready for GenerationResource.
     *
     * @return Collection<int, Generation>
     */
    public function latestForUser(User $user, int $limit): Collection;

    /**
     * The counts on the user's dashboard: finished pictures in total and since
     * $since, and how many are still waiting or being made.
     *
     * @return array{succeeded: int, succeeded_since: int, unfinished: int}
     */
    public function countsForUser(User $user, CarbonImmutable $since): array;

    /**
     * Pictures not sent to Pixelforge yet, oldest first.
     *
     * @return Collection<int, Generation>
     */
    public function waitingForSlot(int $limit): Collection;

    /**
     * Pictures sent to Pixelforge that haven't finished yet. Each one uses a slot.
     *
     * @return Collection<int, Generation>
     */
    public function inFlight(): Collection;

    /**
     * How many slots are in use right now.
     */
    public function countInFlight(): int;

    /**
     * Totals for the admin report: successful pictures between the two dates,
     * grouped by day, user and price list, with what the users were charged.
     *
     * @return list<object{day: string, user_id: int, user_name: string, user_email: string, input_text_nano_per_million: int, input_image_nano_per_million: int, output_nano_per_million: int, reasoning_nano_per_million: int, generations: int, input_text_tokens: int, input_image_tokens: int, output_tokens: int, reasoning_tokens: int, charged_nano: int}>
     */
    public function succeededTotalsByDay(CarbonImmutable $startDate, CarbonImmutable $endDate): array;
}
