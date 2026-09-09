<?php

namespace App\Actions\ExternalShopping;

use App\Models\PurchaseRequest;
use Illuminate\Validation\ValidationException;

class CustomerRejectPurchaseRequestAction
{
    public function execute(PurchaseRequest $purchaseRequest, ?string $reason = null): PurchaseRequest
    {
        if ($purchaseRequest->status !== PurchaseRequest::STATUS_QUOTED) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن الرفض إلا على عرض سعر.',
            ]);
        }

        $purchaseRequest->update([
            'status' => PurchaseRequest::STATUS_REJECTED,
            'customer_notes' => trim(($purchaseRequest->customer_notes ?? '').($reason ? "\nرفض: ".$reason : '')),
        ]);

        return $purchaseRequest->fresh(['items', 'platform']);
    }
}
