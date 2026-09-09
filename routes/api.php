<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\AttributeController as V1AttributeController;
use App\Http\Controllers\Api\V1\BrandController as V1BrandController;
use App\Http\Controllers\Api\V1\CatalogController as V1CatalogController;
use App\Http\Controllers\Api\V1\CouponController as V1CouponController;
use App\Http\Controllers\Api\V1\CurrencyController as V1CurrencyController;
use App\Http\Controllers\Api\V1\ExternalCatalogController as V1ExternalCatalogController;
use App\Http\Controllers\Api\V1\ExternalPlatformController as V1ExternalPlatformController;
use App\Http\Controllers\Api\V1\ExternalProductController as V1ExternalProductController;
use App\Http\Controllers\Api\V1\ImageSearchController as V1ImageSearchController;
use App\Http\Controllers\Api\V1\HomeController as V1HomeController;
use App\Http\Controllers\Api\V1\ProductReviewController as V1ProductReviewController;
use App\Http\Controllers\Api\V1\ReturnExchangeController as V1ReturnExchangeController;
use App\Http\Controllers\Api\V1\ProductQuestionController as V1ProductQuestionController;
use App\Http\Controllers\Api\V1\StoreFollowController as V1StoreFollowController;
use App\Http\Controllers\Api\V1\WishlistController as V1WishlistController;
use App\Http\Controllers\Api\V1\DeviceTokenController as V1DeviceTokenController;
use App\Http\Controllers\Api\V1\MyOrdersController as V1MyOrdersController;
use App\Http\Controllers\Api\V1\NotificationController as V1NotificationController;
use App\Http\Controllers\Api\V1\PurchaseRequestController as V1PurchaseRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/register/verify', [AuthController::class, 'verifyRegistration']);
Route::post('/auth/register/resend-otp', [AuthController::class, 'resendRegistrationOtp']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/providers', [AuthController::class, 'providers']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/otp/send', [AuthController::class, 'sendOtp']);
Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp']);
Route::post('/auth/social', [AuthController::class, 'social']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/avatar', [AuthController::class, 'uploadAvatar']);
    Route::put('/auth/preferences', [AuthController::class, 'updatePreferences']);
    Route::post('/auth/otp/send-auth', [AuthController::class, 'sendOtp']);
    Route::post('/auth/otp/verify-auth', [AuthController::class, 'verifyOtp']);

    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'add']);
    Route::patch('/cart/items/{id}', [CartController::class, 'update']);
    Route::post('/cart/items/{id}/save-for-later', [CartController::class, 'saveForLater']);
    Route::post('/cart/items/{id}/move-to-cart', [CartController::class, 'moveToCart']);
    Route::delete('/cart/items/{id}', [CartController::class, 'remove']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders/{order}/tracking', [OrderController::class, 'tracking']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{order}/reorder', [OrderController::class, 'reorder']);
    Route::post('/orders/{order}/verify-otp', [OrderController::class, 'verifyOtp']);
    Route::post('/orders/{order}/receipt', [OrderController::class, 'uploadReceipt']);
    Route::post('/orders', [OrderController::class, 'store']);

    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::post('/addresses/{address}/default', [AddressController::class, 'setDefault']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/vendors/{vendor}', [VendorController::class, 'show']);
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{user}', [UserController::class, 'update']);

/*
|--------------------------------------------------------------------------
| API v1 routes
|--------------------------------------------------------------------------
|
| New endpoints (catalog additions + the External Shopping "buy anything"
| module) are added here under /api/v1 without touching the legacy flat
| routes above, so existing clients keep working unchanged.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/home', V1HomeController::class);
    Route::get('/catalog', [V1CatalogController::class, 'index']);
    Route::get('/catalog/facets', [V1CatalogController::class, 'facets']);
    Route::get('/catalog/suggestions', [V1CatalogController::class, 'suggestions']);
    Route::get('/catalog/trending-searches', [V1CatalogController::class, 'trendingSearches']);
    Route::get('/catalog/compare', [V1CatalogController::class, 'compare']);
    Route::post('/catalog/compare', [V1CatalogController::class, 'compare']);
    Route::get('/catalog/collections', [V1CatalogController::class, 'collections']);
    Route::get('/catalog/collections/{collection}', [V1CatalogController::class, 'collectionShow']);
    Route::get('/coupons/preview', [V1CouponController::class, 'preview']);
    Route::get('/shipping-methods', [V1CouponController::class, 'shippingMethods']);
    Route::get('/products/{product}/similar', [V1CatalogController::class, 'similar']);
    Route::post('/products/{product}/view', [V1CatalogController::class, 'recordView']);
    Route::get('/products/{product}/reviews', [V1ProductReviewController::class, 'index']);
    Route::get('/currency', [V1CurrencyController::class, 'show']);
    Route::get('/brands', [V1BrandController::class, 'index']);
    Route::get('/brands/{brand}', [V1BrandController::class, 'show']);
    Route::get('/attributes', [V1AttributeController::class, 'index']);
    Route::get('/external-platforms', [V1ExternalPlatformController::class, 'index']);

    Route::get('/browse/{platform}/categories', [V1ExternalCatalogController::class, 'categories']);
    Route::get('/browse/{platform}/products', [V1ExternalCatalogController::class, 'products']);
    Route::get('/browse/{platform}/products/{product}', [V1ExternalCatalogController::class, 'product']);

    Route::post('/external-products/preview', [V1ExternalProductController::class, 'preview']);
    Route::post('/products/search-by-image', V1ImageSearchController::class);

    Route::get('/stores/{vendor}', [V1StoreFollowController::class, 'show']);
    Route::get('/stores/{vendor}/follow-status', [V1StoreFollowController::class, 'status']);
    Route::get('/products/{product}/questions', [V1ProductQuestionController::class, 'index']);

    Route::get('/push/status', [V1DeviceTokenController::class, 'status']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/my-orders', [V1MyOrdersController::class, 'index']);
        Route::post('/device-tokens', [V1DeviceTokenController::class, 'store']);
        Route::delete('/device-tokens', [V1DeviceTokenController::class, 'destroy']);

        Route::get('/wishlist/lists', [V1WishlistController::class, 'lists']);
        Route::post('/wishlist/lists', [V1WishlistController::class, 'createList']);
        Route::put('/wishlist/lists/{list}', [V1WishlistController::class, 'renameList']);
        Route::delete('/wishlist/lists/{list}', [V1WishlistController::class, 'deleteList']);
        Route::post('/wishlist/move', [V1WishlistController::class, 'move']);
        Route::get('/wishlist', [V1WishlistController::class, 'index']);
        Route::get('/wishlist/ids', [V1WishlistController::class, 'ids']);
        Route::post('/wishlist', [V1WishlistController::class, 'store']);
        Route::delete('/wishlist/{productId}', [V1WishlistController::class, 'destroy']);

        Route::get('/store-follows', [V1StoreFollowController::class, 'index']);
        Route::get('/store-follows/feed', [V1StoreFollowController::class, 'feed']);
        Route::post('/stores/{vendor}/follow', [V1StoreFollowController::class, 'follow']);
        Route::delete('/stores/{vendor}/follow', [V1StoreFollowController::class, 'unfollow']);

        Route::post('/products/{product}/questions', [V1ProductQuestionController::class, 'store']);

        Route::get('/orders/{order}/reviewable-items', [V1ProductReviewController::class, 'reviewableItems']);
        Route::post('/reviews', [V1ProductReviewController::class, 'store']);
        Route::get('/orders/{order}/return-exchange-items', [V1ReturnExchangeController::class, 'eligible']);
        Route::get('/orders/{order}/return-exchange-requests', [V1ReturnExchangeController::class, 'forOrder']);
        Route::post('/return-exchange-requests', [V1ReturnExchangeController::class, 'store']);
        Route::get('/return-exchange-requests', [V1ReturnExchangeController::class, 'mine']);
        Route::get('/return-exchange-requests/{returnExchangeRequest}', [V1ReturnExchangeController::class, 'show']);
        Route::get('/notifications', [V1NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [V1NotificationController::class, 'markAllRead']);
        Route::post('/notifications/{id}/read', [V1NotificationController::class, 'markRead']);
        Route::get('/purchase-requests', [V1PurchaseRequestController::class, 'index']);
        Route::get('/purchase-requests/{purchaseRequest}', [V1PurchaseRequestController::class, 'show']);
        Route::post('/purchase-requests', [V1PurchaseRequestController::class, 'store']);
        Route::post('/purchase-requests/{purchaseRequest}/approve', [V1PurchaseRequestController::class, 'approve']);
        Route::post('/purchase-requests/{purchaseRequest}/reject', [V1PurchaseRequestController::class, 'reject']);
        Route::post('/purchase-requests/{purchaseRequest}/payment', [V1PurchaseRequestController::class, 'submitPayment']);
    });
});
