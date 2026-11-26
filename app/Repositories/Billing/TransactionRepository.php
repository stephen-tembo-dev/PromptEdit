<?php

namespace App\Repositories\Billing;

use App\Enums\Billing\TransactionKind;
use App\Models\User\User;
use App\Repositories\Contracts\Billing\TransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Builds the customer's money history from credit_purchases and ledger_entries.
 */
class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * Both tables are joined into one list with UNION ALL, so the database
     * does the sorting and paging across them.
     *
     * @param  list<TransactionKind>|null  $kinds
     * @return LengthAwarePaginator<int, object{kind: string, source_id: int, amount_nano: int, created_at: string, generation_uuid: string|null, prompt: string|null}>
     */
    public function paginateForUser(User $user, ?array $kinds, int $perPage): LengthAwarePaginator
    {
        $purchases = DB::table('credit_purchases')
            ->where('user_id', $user->id)
            ->selectRaw('? AS kind, id AS source_id, amount_nano, created_at, NULL AS generation_uuid, NULL AS prompt', [TransactionKind::Purchase->value]);

        $ledger = DB::table('ledger_entries')
            ->join('generations', 'generations.id', '=', 'ledger_entries.generation_id')
            ->where('generations.user_id', $user->id)
            ->select([
                'ledger_entries.type AS kind',
                'ledger_entries.id AS source_id',
                'ledger_entries.amount_nano',
                'ledger_entries.created_at',
                'generations.uuid AS generation_uuid',
                'generations.prompt',
            ]);

        return DB::query()
            ->fromSub($purchases->unionAll($ledger), 'transactions')
            ->when($kinds, fn ($query, array $kinds) => $query->whereIn('kind', array_map(fn (TransactionKind $kind): string => $kind->value, $kinds)))
            ->orderByDesc('created_at')
            ->orderByDesc('source_id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
