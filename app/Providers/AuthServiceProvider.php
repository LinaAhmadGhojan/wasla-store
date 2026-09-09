<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Order;
use App\Models\PurchaseRequest;
use App\Policies\AddressPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PurchaseRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Address::class => AddressPolicy::class,
        Order::class => OrderPolicy::class,
        PurchaseRequest::class => PurchaseRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
