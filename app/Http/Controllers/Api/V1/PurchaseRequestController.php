<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ExternalShopping\CustomerApprovePurchaseRequestAction;
use App\Actions\ExternalShopping\CustomerRejectPurchaseRequestAction;
use App\Actions\ExternalShopping\CreatePurchaseRequestAction;
use App\Actions\ExternalShopping\SubmitPurchaseRequestPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePurchaseRequestRequest;
use App\Http\Resources\CustomerPurchaseRequestResource;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index(Request $request)
    {
        $purchaseRequests = $request->user()->purchaseRequests()
            ->with(['items', 'platform', 'latestPayment'])
            ->orderByDesc('created_at')
            ->get();

        return CustomerPurchaseRequestResource::collection($purchaseRequests);
    }

    public function show(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorize('view', $purchaseRequest);

        return new CustomerPurchaseRequestResource(
            $purchaseRequest->load(['items.externalProduct', 'platform', 'latestPayment'])
        );
    }

    public function store(StorePurchaseRequestRequest $request, CreatePurchaseRequestAction $action)
    {
        $purchaseRequest = $action->execute($request->user(), $request->validated());

        return (new CustomerPurchaseRequestResource(
            $purchaseRequest->load(['items', 'platform'])
        ))->response()->setStatusCode(201);
    }

    public function approve(PurchaseRequest $purchaseRequest, CustomerApprovePurchaseRequestAction $action)
    {
        $this->authorize('approve', $purchaseRequest);

        return new CustomerPurchaseRequestResource(
            $action->execute($purchaseRequest)->load(['items', 'platform', 'latestPayment'])
        );
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest, CustomerRejectPurchaseRequestAction $action)
    {
        $this->authorize('reject', $purchaseRequest);

        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        return new CustomerPurchaseRequestResource(
            $action->execute($purchaseRequest, $data['reason'] ?? null)->load(['items', 'platform'])
        );
    }

    public function submitPayment(Request $request, PurchaseRequest $purchaseRequest, SubmitPurchaseRequestPaymentAction $action)
    {
        $this->authorize('pay', $purchaseRequest);

        $data = $request->validate([
            'method' => 'required|string|in:'.implode(',', array_keys(config('payments.methods'))),
            'amount_syp' => 'nullable|integer|min:1',
            'customer_notes' => 'nullable|string|max:500',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $action->execute($purchaseRequest, $data, $request->file('receipt'));

        return new CustomerPurchaseRequestResource(
            $purchaseRequest->fresh()->load(['items', 'platform', 'latestPayment'])
        );
    }
}
