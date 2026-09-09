<?php

namespace App\Actions\ExternalShopping;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestPayment;
use App\Models\StoreCreditTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmitPurchaseRequestPaymentAction
{
    public function execute(PurchaseRequest $purchaseRequest, array $data, ?UploadedFile $receipt = null): PurchaseRequestPayment
    {
        if ($purchaseRequest->status !== PurchaseRequest::STATUS_CUSTOMER_APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'يجب الموافقة على العرض قبل الدفع.',
            ]);
        }

        if ($purchaseRequest->payments()->where('status', PurchaseRequestPayment::STATUS_SUBMITTED)->exists()) {
            throw ValidationException::withMessages([
                'payment' => 'يوجد دفعة بانتظار التأكيد مسبقاً.',
            ]);
        }

        $method    = $data['method'];
        $amountSyp = (int) ($data['amount_syp'] ?? $purchaseRequest->final_price_syp);

        if ($amountSyp <= 0) {
            throw ValidationException::withMessages(['amount_syp' => 'المبلغ غير صالح.']);
        }

        $allowedMethods = array_keys(config('payments.methods', []));
        if (! in_array($method, $allowedMethods, true)) {
            throw ValidationException::withMessages(['method' => 'طريقة الدفع غير صالحة.']);
        }

        // Store credit: debit immediately and auto-confirm
        if ($method === 'store_credit') {
            $customer = $purchaseRequest->customer;
            if (! $customer || $customer->store_credit_syp < $amountSyp) {
                throw ValidationException::withMessages([
                    'method' => 'رصيد وصلة غير كافٍ (رصيدك: '.number_format($customer?->store_credit_syp ?? 0).' ل.س).',
                ]);
            }

            $payment = PurchaseRequestPayment::create([
                'purchase_request_id' => $purchaseRequest->id,
                'amount_syp'          => $amountSyp,
                'method'              => $method,
                'status'              => PurchaseRequestPayment::STATUS_CONFIRMED,
                'customer_notes'      => $data['customer_notes'] ?? null,
                'submitted_at'        => now(),
                'confirmed_at'        => now(),
            ]);

            $customer->debitStoreCredit($amountSyp, StoreCreditTransaction::TYPE_PAYMENT, $payment, 'خصم مقابل طلب #'.$purchaseRequest->id);

            $purchaseRequest->update(['status' => PurchaseRequest::STATUS_PAID]);

            return $payment;
        }

        // Cash on delivery: record as submitted (admin confirms on delivery)
        $receiptPath = null;
        if ($receipt && ! in_array($method, config('payments.no_receipt', []), true)) {
            $receiptPath = $receipt->store('receipts/'.date('Y/m'), 'public');
        }

        return PurchaseRequestPayment::create([
            'purchase_request_id' => $purchaseRequest->id,
            'amount_syp'          => $amountSyp,
            'method'              => $method,
            'status'              => PurchaseRequestPayment::STATUS_SUBMITTED,
            'receipt_path'        => $receiptPath,
            'customer_notes'      => $data['customer_notes'] ?? null,
            'submitted_at'        => now(),
        ]);
    }
}
