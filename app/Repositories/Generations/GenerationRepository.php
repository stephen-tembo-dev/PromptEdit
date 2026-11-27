<?php

namespace App\Repositories\Generations;

use App\Enums\Billing\LedgerEntryType;
use App\Enums\Generations\GenerationStatus;
use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Models\Generations\Generation;
use App\Models\User\User;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use App\Traits\RequiresTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * All the database queries for pictures.
 */
class GenerationRepository implements GenerationRepositoryInterface
{
    use RequiresTransaction;

    /**
     * Save a new picture and its reference image links together.
     */
    public function create(
        User $user,
        string $prompt,
        ImageSize $size,
        ImageQuality $quality,
        array $referenceImageUrls,
        int $estimatedChargeNano,
    ): Generation {
        return DB::transaction(function () use ($user, $prompt, $size, $quality, $referenceImageUrls, $estimatedChargeNano): Generation {
            $generation = $user->generations()->make([
                'prompt' => $prompt,
                'size' => $size,
                'quality' => $quality,
            ]);
            $generation->forceFill(['estimated_charge_nano' => $estimatedChargeNano])->save();

            $generation->referenceImages()->createMany(
                collect($referenceImageUrls)
                    ->values()
                    ->map(fn (string $url, int $position): array => ['position' => $position, 'url' => $url])
                    ->all(),
            );

            return $generation;
        });
    }

    /**
     * Give a waiting picture its slot.
     */
    public function markClaimed(Generation $generation): void
    {
        $generation->forceFill(['submitted_at' => now()])->save();
    }

    /**
     * Count one more try at sending the picture.
     */
    public function recordSubmitAttempt(Generation $generation): void
    {
        $generation->forceFill([
            'submit_attempts' => $generation->submit_attempts + 1,
            'last_submit_attempt_at' => now(),
        ])->save();
    }

    /**
     * Count one more "overloaded" answer.
     */
    public function recordOverload(Generation $generation): void
    {
        $generation->forceFill(['overload_attempts' => $generation->overload_attempts + 1])->save();
    }

    /**
     * Remember that Pixelforge may have the picture.
     */
    public function markMayExistAtProvider(Generation $generation): void
    {
        $generation->forceFill(['may_exist_at_provider' => true])->save();
    }

    /**
     * Mark the picture as failed.
     */
    public function markFailed(Generation $generation, string $errorCode, string $errorMessage): void
    {
        $generation->forceFill([
            'status' => GenerationStatus::Failed,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ])->save();
    }

    /**
     * Save what Pixelforge told us about the picture.
     */
    public function update(Generation $generation, array $attributes): void
    {
        $generation->forceFill($attributes)->save();
    }

    /**
     * Find a picture by our uuid.
     */
    public function findByUuid(string $uuid): ?Generation
    {
        return Generation::query()->where('uuid', $uuid)->first();
    }

    /**
     * Find a picture and lock its row. Must be called inside a transaction.
     */
    public function lockByUuid(string $uuid): ?Generation
    {
        $this->ensureInTransaction('Locking a generation');

        return Generation::query()->where('uuid', $uuid)->lockForUpdate()->first();
    }

    /**
     * One page of the user's pictures, newest first, with their images and money lines.
     */
    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->generations()
            ->with(['referenceImages', 'ledgerEntries'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Pictures that haven't been sent yet, oldest first.
     */
    public function waitingForSlot(int $limit): Collection
    {
        return Generation::query()
            ->where('status', GenerationStatus::Queued)
            ->whereNull('submitted_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Pictures sent to Pixelforge that haven't finished, oldest first.
     */
    public function inFlight(): Collection
    {
        return $this->inFlightQuery()->orderBy('submitted_at')->get();
    }

    /**
     * How many pictures are using a slot right now.
     */
    /**
     * The dashboard counts in one query.
     *
     * @return array{succeeded: int, succeeded_since: int, unfinished: int}
     */
    public function countsForUser(User $user, CarbonImmutable $since): array
    {
        $succeeded = GenerationStatus::Succeeded->value;

        $row = $user->generations()->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS succeeded', [$succeeded])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? AND completed_at >= ? THEN 1 ELSE 0 END), 0) AS succeeded_since', [$succeeded, $since])
            ->selectRaw('COALESCE(SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END), 0) AS unfinished', [GenerationStatus::Queued->value, GenerationStatus::Processing->value])
            ->first();

        return [
            'succeeded' => (int) $row->succeeded,
            'succeeded_since' => (int) $row->succeeded_since,
            'unfinished' => (int) $row->unfinished,
        ];
    }

    public function countInFlight(): int
    {
        return $this->inFlightQuery()->count();
    }

    /**
     * Totals for the admin report. The database does the adding up, so we get
     * back a few rows per day instead of every single picture. Each picture has
     * at most one charge, so joining the charges doesn't count anything twice.
     */
    public function succeededTotalsByDay(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $rateColumns = ['input_text_nano_per_million', 'input_image_nano_per_million', 'output_nano_per_million', 'reasoning_nano_per_million'];
        $tokenColumns = ['input_text_tokens', 'input_image_tokens', 'output_tokens', 'reasoning_tokens'];

        return Generation::query()
            ->toBase()
            ->join('users', 'users.id', '=', 'generations.user_id')
            ->join('rate_cards', 'rate_cards.id', '=', 'generations.rate_card_id')
            ->leftJoin('ledger_entries as charges', fn (JoinClause $join) => $join
                ->on('charges.generation_id', '=', 'generations.id')
                ->where('charges.type', LedgerEntryType::Charge->value))
            ->where('generations.status', GenerationStatus::Succeeded->value)
            ->where('generations.completed_at', '>=', $startDate->startOfDay())
            ->where('generations.completed_at', '<', $endDate->addDay()->startOfDay())
            ->selectRaw('DATE(generations.completed_at) AS day')
            ->addSelect(['users.id AS user_id', 'users.name AS user_name', 'users.email AS user_email'])
            ->addSelect(array_map(fn (string $column): string => "rate_cards.{$column}", $rateColumns))
            ->selectRaw('COUNT(*) AS generations')
            ->selectRaw(implode(', ', array_map(fn (string $column): string => "SUM(generations.{$column}) AS {$column}", $tokenColumns)))
            ->selectRaw('COALESCE(SUM(charges.amount_nano), 0) AS charged_nano')
            ->groupByRaw('DATE(generations.completed_at)')
            ->groupBy(['users.id', 'users.name', 'users.email', 'rate_cards.id', ...array_map(fn (string $column): string => "rate_cards.{$column}", $rateColumns)])
            ->orderBy('day')
            ->get()
            ->map(function (object $row) use ($rateColumns, $tokenColumns): object {
                // The database can return numbers as text, so turn them into integers.
                foreach (['user_id', 'generations', 'charged_nano', ...$rateColumns, ...$tokenColumns] as $column) {
                    $row->{$column} = (int) $row->{$column};
                }

                return $row;
            })
            ->all();
    }

    /**
     * Pictures that are processing, or queued at Pixelforge (sent but not started).
     *
     * @return Builder<Generation>
     */
    private function inFlightQuery(): Builder
    {
        return Generation::query()->where(function (Builder $query): void {
            $query->where('status', GenerationStatus::Processing)
                ->orWhere(fn (Builder $queued) => $queued
                    ->where('status', GenerationStatus::Queued)
                    ->whereNotNull('submitted_at'));
        });
    }
}
