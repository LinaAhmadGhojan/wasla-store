<?php

use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\AttributeValueController as AdminAttributeValueController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExchangeRateController as AdminExchangeRateController;
use App\Http\Controllers\Admin\ExternalPlatformController as AdminExternalPlatformController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UnifiedOrderController as AdminUnifiedOrderController;
use App\Http\Controllers\Admin\ProcurementBatchController as AdminProcurementBatchController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\PurchaseRequestController as AdminPurchaseRequestController;
use App\Http\Controllers\Admin\SectionController as AdminSectionController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\WhatsappBroadcastController as AdminWhatsappBroadcastController;
use App\Http\Controllers\Admin\WhatsappConnectionController as AdminWhatsappConnectionController;
use App\Http\Controllers\Admin\WhatsappGroupController as AdminWhatsappGroupController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [StorefrontController::class, 'shop'])->name('home');
Route::get('/sitemap.xml', [StorefrontController::class, 'sitemap'])->name('sitemap');
Route::get('/shop', [StorefrontController::class, 'shop'])->name('storefront.shop');
Route::get('/compare', [StorefrontController::class, 'compare'])->name('storefront.compare');
Route::get('/product/{product}', [StorefrontController::class, 'productShow'])->name('storefront.product');
Route::get('/products/{product}', [StorefrontController::class, 'productShow'])->whereNumber('product');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('storefront.cart');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('storefront.checkout');
Route::get('/my-requests', [StorefrontController::class, 'myRequests'])->name('storefront.my-requests');
Route::get('/profile', [StorefrontController::class, 'profile'])->name('storefront.profile');
Route::get('/addresses', [StorefrontController::class, 'addresses'])->name('storefront.addresses');
Route::get('/favorites', [StorefrontController::class, 'favorites'])->name('storefront.favorites');
Route::get('/stores/{vendor}', [StorefrontController::class, 'storeShow'])->name('storefront.store');
Route::get('/orders/{order}/track', [StorefrontController::class, 'trackOrder'])->name('storefront.order-track');
Route::get('/orders/{order}/invoice', [StorefrontController::class, 'orderInvoice'])->name('storefront.order-invoice');
Route::get('/order-confirmation', [StorefrontController::class, 'orderConfirmation'])->name('storefront.order-confirmation');
Route::get('/login', [StorefrontController::class, 'login'])->name('storefront.login');
Route::get('/register', [StorefrontController::class, 'register'])->name('storefront.register');
Route::get('/forgot-password', [StorefrontController::class, 'forgotPassword'])->name('storefront.forgot-password');
Route::get('/reset-password', [StorefrontController::class, 'resetPassword'])->name('storefront.reset-password');
Route::get('/buy-from-anywhere', [StorefrontController::class, 'buyFromAnywhere'])->name('storefront.buy-from-anywhere');
Route::get('/browse', [StorefrontController::class, 'browseHub'])->name('storefront.browse');
Route::get('/browse/{platform}', [StorefrontController::class, 'browsePlatform'])->name('storefront.browse.platform');
Route::get('/browse/{platform}/product/{product}', [StorefrontController::class, 'browseProduct'])->name('storefront.browse.product');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('notifications/poll', [AdminNotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('notifications/read-all', [AdminNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{alert}/read', [AdminNotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/', [AdminDashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', AdminUserController::class)->except(['show']);
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::post('products/{product}/colors', [AdminProductController::class, 'updateColor'])->name('products.colors.update');
    Route::delete('products/{product}/colors', [AdminProductController::class, 'destroyColor'])->name('products.colors.destroy');
    Route::get('products/{product}/whatsapp', [AdminProductController::class, 'whatsappShare'])->name('products.whatsapp');
    Route::post('products/{product}/whatsapp/send', [AdminProductController::class, 'whatsappSend'])->name('products.whatsapp.send');
    Route::post('products/whatsapp/bulk-send', [AdminProductController::class, 'whatsappBulkSend'])->name('products.whatsapp.bulk');
    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::resource('vendors', AdminVendorController::class)->except(['show']);
    Route::resource('brands', AdminBrandController::class)->except(['show']);

    Route::resource('attributes', AdminAttributeController::class)->except(['show']);
    Route::post('attributes/{attribute}/values', [AdminAttributeValueController::class, 'store'])->name('attributes.values.store');
    Route::delete('attributes/{attribute}/values/{value}', [AdminAttributeValueController::class, 'destroy'])->name('attributes.values.destroy');

    Route::resource('external-platforms', AdminExternalPlatformController::class)->except(['show']);
    Route::get('exchange-rate', [AdminExchangeRateController::class, 'edit'])->name('exchange-rate.edit');
    Route::put('exchange-rate', [AdminExchangeRateController::class, 'update'])->name('exchange-rate.update');

    Route::resource('whatsapp-groups', AdminWhatsappGroupController::class)->except(['show']);
    Route::post('whatsapp-groups/{whatsapp_group}/sync-chat-id', [AdminWhatsappGroupController::class, 'syncChatId'])->name('whatsapp-groups.sync-chat-id');
    Route::resource('whatsapp-messages', AdminWhatsappBroadcastController::class)->except(['show']);
    Route::post('whatsapp-messages/{whatsapp_message}/send', [AdminWhatsappBroadcastController::class, 'send'])->name('whatsapp-messages.send');
    Route::get('whatsapp/connection', [AdminWhatsappConnectionController::class, 'show'])->name('whatsapp.connection');
    Route::get('whatsapp/status', [AdminWhatsappConnectionController::class, 'status'])->name('whatsapp.status');
    Route::post('whatsapp/reset', [AdminWhatsappConnectionController::class, 'reset'])->name('whatsapp.reset');
    Route::get('whatsapp/groups', [AdminWhatsappConnectionController::class, 'groups'])->name('whatsapp.groups');

    Route::get('purchase-requests', [AdminPurchaseRequestController::class, 'index'])->name('purchase-requests.index');
    Route::get('purchase-requests/{purchaseRequest}', [AdminPurchaseRequestController::class, 'show'])->name('purchase-requests.show');
    Route::put('purchase-requests/{purchaseRequest}/quote', [AdminPurchaseRequestController::class, 'quote'])->name('purchase-requests.quote');
    Route::patch('purchase-requests/{purchaseRequest}/status', [AdminPurchaseRequestController::class, 'updateStatus'])->name('purchase-requests.status');
    Route::post('purchase-requests/{purchaseRequest}/confirm-payment', [AdminPurchaseRequestController::class, 'confirmPayment'])->name('purchase-requests.confirm-payment');
    Route::post('purchase-requests/{purchaseRequest}/reject-payment', [AdminPurchaseRequestController::class, 'rejectPayment'])->name('purchase-requests.reject-payment');
    Route::post('customers/{user}/add-credit', [AdminUserController::class, 'addCredit'])->name('customers.add-credit');

    // Orders — unified inbox + local details
    Route::get('orders', [AdminUnifiedOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/local', [AdminOrderController::class, 'index'])->name('orders.local');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/confirm-payment', [AdminOrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
    Route::post('orders/{order}/assign-driver', [AdminOrderController::class, 'assignDriver'])->name('orders.assign-driver');
    Route::post('orders/{order}/logistics', [AdminOrderController::class, 'addLogistics'])->name('orders.logistics');
    Route::post('orders/{order}/regenerate-otp', [AdminOrderController::class, 'regenerateOtp'])->name('orders.regenerate-otp');
    Route::post('orders/{order}/verify-otp', [AdminOrderController::class, 'verifyOtp'])->name('orders.verify-otp');
    Route::post('orders/{order}/deliver', [AdminOrderController::class, 'markDelivered'])->name('orders.deliver');
    Route::post('orders/{order}/fail-delivery', [AdminOrderController::class, 'markFailed'])->name('orders.fail-delivery');

    // Settings
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');

    Route::resource('procurement-batches', AdminProcurementBatchController::class)->except(['edit', 'update', 'destroy']);
    Route::patch('procurement-batches/{procurement_batch}', [AdminProcurementBatchController::class, 'update'])->name('procurement-batches.update');
    Route::post('procurement-batches/{procurement_batch}/add/{purchaseRequest}', [AdminProcurementBatchController::class, 'addRequest'])->name('procurement-batches.add-request');
    Route::post('procurement-batches/{procurement_batch}/remove/{purchaseRequest}', [AdminProcurementBatchController::class, 'removeRequest'])->name('procurement-batches.remove-request');

    Route::get('/{section}', [AdminSectionController::class, 'show'])->name('section');
});
