<?php

namespace Tests\Feature\Repositories\Billing;

use App\Models\User\User;
use App\Repositories\Contracts\Billing\CreditPurchaseRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreditPurchaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_resending_the_same_key_adds_credits_only_once(): void
    {
        $purchases = app(CreditPurchaseRepositoryInterface::class);
        $user = User::factory()->create();
        $key = (string) Str::uuid();

        $first = $purchases->recordOnce($user, 10_000_000_000, $key);
        $replay = $purchases->recordOnce($user, 10_000_000_000, $key);

        $this->assertTrue($first->is($replay));
        $this->assertSame(10_000_000_000, $purchases->totalForUser($user));
    }

    public function test_a_key_already_used_by_another_user_is_rejected(): void
    {
        $purchases = app(CreditPurchaseRepositoryInterface::class);
        $key = (string) Str::uuid();
        $purchases->recordOnce(User::factory()->create(), 10_000_000_000, $key);

        $this->expectException(UniqueConstraintViolationException::class);

        $purchases->recordOnce(User::factory()->create(), 10_000_000_000, $key);
    }
}
