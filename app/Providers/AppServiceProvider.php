<?php

namespace App\Providers;

use App\Contracts\ExternalCatalogBrowserInterface;
use App\Contracts\ExternalPlatformServiceInterface;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\ExchangeRate;
use App\Models\ExpressCategory;
use App\Models\ExpressMenuItem;
use App\Models\ExpressMenuItemExtra;
use App\Models\ExpressMenuItemVariant;
use App\Models\ExpressStore;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Observers\CatalogCacheObserver;
use App\Observers\ExpressCacheObserver;
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

        $observer = CatalogCacheObserver::class;
        Product::observe($observer);
        ProductVariant::observe($observer);
        ProductImage::observe($observer);
        Category::observe($observer);
        Brand::observe($observer);
        Vendor::observe($observer);
        Collection::observe($observer);
        ExchangeRate::observe($observer);

        $expressObserver = ExpressCacheObserver::class;
        ExpressCategory::observe($expressObserver);
        ExpressStore::observe($expressObserver);
        ExpressMenuItem::observe($expressObserver);
        ExpressMenuItemVariant::observe($expressObserver);
        ExpressMenuItemExtra::observe($expressObserver);
    }
}
