<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CurrencyService;

class DashboardController extends Controller
{
    public function index(CurrencyService $currency)
    {
        $prCounts = PurchaseRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'userCount' => User::count(),
            'productCount' => Product::count(),
            'categoryCount' => Category::count(),
            'vendorCount' => Vendor::count(),
            'exchangeRate' => $currency->currentAedToSypRate(),
            'productFee' => $currency->productFeeAed(),
            'accessoryFee' => $currency->accessoryFeeAed(),
            'catalogProducts' => Product::with(['images', 'variants', 'category.parent'])
                ->where('is_active', true)
                ->latest()
                ->limit(4)
                ->get(),
            'prCounts' => $prCounts,
            'prNew' => (int) ($prCounts[PurchaseRequest::STATUS_PENDING] ?? 0)
                + (int) ($prCounts[PurchaseRequest::STATUS_UNDER_REVIEW] ?? 0),
            'prAwaitingPayment' => (int) ($prCounts[PurchaseRequest::STATUS_QUOTED] ?? 0)
                + (int) ($prCounts[PurchaseRequest::STATUS_CUSTOMER_APPROVED] ?? 0),
            'prPurchasing' => (int) ($prCounts[PurchaseRequest::STATUS_PAID] ?? 0)
                + (int) ($prCounts[PurchaseRequest::STATUS_PURCHASING] ?? 0),
            'prReady' => (int) ($prCounts[PurchaseRequest::STATUS_PURCHASED] ?? 0)
                + (int) ($prCounts[PurchaseRequest::STATUS_RECEIVED] ?? 0),
            'statusLabels' => PurchaseRequest::STATUS_LABELS,
        ]);
    }
}
