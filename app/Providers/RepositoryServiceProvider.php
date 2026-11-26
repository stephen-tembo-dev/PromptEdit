<?php

namespace App\Providers;

use App\Repositories\Billing\CreditPurchaseRepository;
use App\Repositories\Billing\LedgerEntryRepository;
use App\Repositories\Billing\RateCardRepository;
use App\Repositories\Billing\TransactionRepository;
use App\Repositories\Contracts\Billing\CreditPurchaseRepositoryInterface;
use App\Repositories\Contracts\Billing\LedgerEntryRepositoryInterface;
use App\Repositories\Contracts\Billing\RateCardRepositoryInterface;
use App\Repositories\Contracts\Billing\TransactionRepositoryInterface;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use App\Repositories\Contracts\User\RoleRepositoryInterface;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use App\Repositories\Contracts\Webhooks\WebhookEventRepositoryInterface;
use App\Repositories\Generations\GenerationRepository;
use App\Repositories\User\RoleRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Webhooks\WebhookEventRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Tells Laravel which class to use for each repository interface. Each one is
 * created once and shared, because they don't store anything themselves.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Each interface and the class that does the work.
     *
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        // Billing
        CreditPurchaseRepositoryInterface::class => CreditPurchaseRepository::class,
        LedgerEntryRepositoryInterface::class => LedgerEntryRepository::class,
        RateCardRepositoryInterface::class => RateCardRepository::class,
        TransactionRepositoryInterface::class => TransactionRepository::class,

        // Generations
        GenerationRepositoryInterface::class => GenerationRepository::class,

        // User
        RoleRepositoryInterface::class => RoleRepository::class,
        UserRepositoryInterface::class => UserRepository::class,

        // Webhooks
        WebhookEventRepositoryInterface::class => WebhookEventRepository::class,
    ];
}
