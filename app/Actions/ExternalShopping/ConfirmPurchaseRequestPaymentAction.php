<?php

namespace App\Actions\ExternalShopping;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestPayment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ConfirmPurchaseRequestPaymentAction
{
    public function execute(PurchaseRequestPayment $payment, User $admin, ?string $adminNotes = null): PurchaseRequest
    {
        if ($payment->status !== PurchaseRequestPayment::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'payment' => 'هذه الدفعة ليست بانتظار التأكيد.',
            ]);
        }

        $payment->update([
            'status' => PurchaseRequestPayment::STATUS_CONFIRMED,
            'admin_notes' => $adminNotes,
            'confirmed_at' => now(),
            'confirmed_by' => $admin->id,
        ]);

        $purchaseRequest = $payment->purchaseRequest;
        $purchaseRequest->update(['status' => PurchaseRequest::STATUS_PAID]);

        $fresh = $purchaseRequest->fresh(['items', 'platform', 'latestPayment', 'procurementBatch', 'customer']);
        app(\App\Services\Orders\CustomerOrderNotifier::class)->notifyExternalStatus($fresh);

        return $fresh;
    }
}
