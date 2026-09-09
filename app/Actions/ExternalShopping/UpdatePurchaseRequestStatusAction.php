<?php

namespace App\Actions\ExternalShopping;

use App\Models\PurchaseRequest;
use App\Services\Orders\CustomerOrderNotifier;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseRequestStatusAction
{
    /**
     * The forward workflow. Cancellation/rejection is allowed from any
     * non-terminal state, handled separately below.
     */
    private const FORWARD_FLOW = [
        PurchaseRequest::STATUS_PENDING => PurchaseRequest::STATUS_UNDER_REVIEW,
        PurchaseRequest::STATUS_UNDER_REVIEW => PurchaseRequest::STATUS_QUOTED,
        PurchaseRequest::STATUS_QUOTED => PurchaseRequest::STATUS_CUSTOMER_APPROVED,
        PurchaseRequest::STATUS_CUSTOMER_APPROVED => PurchaseRequest::STATUS_PAID,
        PurchaseRequest::STATUS_PAID => PurchaseRequest::STATUS_PURCHASING,
        PurchaseRequest::STATUS_PURCHASING => PurchaseRequest::STATUS_PURCHASED,
        PurchaseRequest::STATUS_PURCHASED => PurchaseRequest::STATUS_RECEIVED,
    ];

    private const TERMINAL_STATUSES = [
        PurchaseRequest::STATUS_RECEIVED,
        PurchaseRequest::STATUS_CANCELLED,
        PurchaseRequest::STATUS_REJECTED,
    ];

    public function execute(PurchaseRequest $purchaseRequest, string $status): PurchaseRequest
    {
        $current = $purchaseRequest->status;

        if (in_array($current, self::TERMINAL_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => "Request is already in a terminal state ({$current}) and cannot change.",
            ]);
        }

        $isCancelOrReject = in_array($status, [PurchaseRequest::STATUS_CANCELLED, PurchaseRequest::STATUS_REJECTED], true);
        $isValidForwardStep = (self::FORWARD_FLOW[$current] ?? null) === $status;

        if (! $isCancelOrReject && ! $isValidForwardStep) {
            throw ValidationException::withMessages([
                'status' => "Cannot move purchase request from '{$current}' to '{$status}'.",
            ]);
        }

        $purchaseRequest->update(['status' => $status]);

        $fresh = $purchaseRequest->fresh(['customer', 'platform']);
        app(CustomerOrderNotifier::class)->notifyExternalStatus($fresh);

        return $fresh;
    }
}
