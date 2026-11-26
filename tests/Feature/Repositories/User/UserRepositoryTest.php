<?php

namespace Tests\Feature\Repositories\User;

use App\Models\User\User;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * Uses DatabaseMigrations rather than RefreshDatabase: RefreshDatabase wraps each
 * test in a transaction, which would hide a missing one from the guard.
 */
class UserRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    public function test_locking_a_user_requires_a_transaction(): void
    {
        $users = app(UserRepositoryInterface::class);
        $user = User::factory()->create();

        $locked = DB::transaction(fn () => $users->lockForUpdate($user));
        $this->assertTrue($user->is($locked));

        $this->expectException(LogicException::class);

        $users->lockForUpdate($user);
    }
}
