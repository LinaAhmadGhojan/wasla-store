<?php

namespace App\Actions\ExternalShopping;

use App\Models\PurchaseRequest;
use Illuminate\Validation\ValidationException;

class CustomerApprovePurchaseRequestAction
{
    public function execute(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        if ($purchaseRequest->status !== PurchaseRequest::STATUS_QUOTED) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن الموافقة إلا على عرض سعر جاهز.',
            ]);
        }

        if ($purchaseRequest->final_price_syp === null) {
            throw ValidationException::withMessages([
                'quote' => 'عرض السعر غير مكتمل بعد.',
            ]);
        }

        $purchaseRequest->update([
            'status' => PurchaseRequest::STATUS_CUSTOMER_APPROVED,
            'customer_approved_at' => now(),
        ]);

        $fresh = $purchaseRequest->fresh(['items', 'platform', 'latestPayment', 'customer']);
        app(\App\Services\Orders\CustomerOrderNotifier::class)->notifyExternalStatus($fresh);

        return $fresh;
    }
}
