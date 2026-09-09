<?php

namespace App\Providers;

use App\Contracts\ExternalCatalogBrowserInterface;
use App\Contracts\ExternalPlatformServiceInterface;
use App\Services\ExternalShopping\ManualReviewPlatformService;
use App\Services\ExternalShopping\MockExternalCatalogBrowser;
use App\Services\ExternalShopping\SearchApiExternalCatalogBrowser;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bound to the manual-review stub for now. Once real platform adapters
        // (SHEIN, Trendyol, Temu, Noon, Amazon...) exist, resolve them here
        // based on the platform slug instead of a single global binding.
        $this->app->bind(ExternalPlatformServiceInterface::class, ManualReviewPlatformService::class);

        $this->app->singleton(MockExternalCatalogBrowser::class);

        $this->app->bind(ExternalCatalogBrowserInterface::class, function ($app) {
            $driver = config('external_shopping.catalog_driver', 'mock');

            return match ($driver) {
                'searchapi' => $app->make(SearchApiExternalCatalogBrowser::class),
                default => $app->make(MockExternalCatalogBrowser::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return url('/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset()));
        });
    }
}
