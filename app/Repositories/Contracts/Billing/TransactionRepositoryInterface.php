<?php

namespace App\Repositories\Contracts\Billing;

use App\Enums\Billing\TransactionKind;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The customer's money history: credit purchases and ledger lines in one list.
 */
interface TransactionRepositoryInterface
{
    /**
     * One page of the user's transactions, newest first. Pass $kinds to show
     * only those kinds of line.
     *
     * @param  list<TransactionKind>|null  $kinds
     * @return LengthAwarePaginator<int, object{kind: string, source_id: int, amount_nano: int, created_at: string, generation_uuid: string|null, prompt: string|null}>
     */
    public function paginateForUser(User $user, ?array $kinds, int $perPage): LengthAwarePaginator;
}
