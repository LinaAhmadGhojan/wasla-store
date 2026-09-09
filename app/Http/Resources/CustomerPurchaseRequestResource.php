<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Customer-facing purchase request — SYP only, no AED procurement fields. */
class CustomerPurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'url' => $this->url,
            'customer_notes' => $this->customer_notes,
            'platform' => $this->whenLoaded('platform', fn () => [
                'id' => $this->platform?->id,
                'name' => $this->platform?->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'variant_data' => $item->variant_data,
                'quantity' => $item->quantity,
                'final_price_syp' => $item->final_price_syp,
            ])),
            'final_price_syp' => $this->final_price_syp,
            'estimated_price_syp' => $this->estimated_price_syp,
            'quoted_at' => $this->quoted_at?->toIso8601String(),
            'customer_approved_at' => $this->customer_approved_at?->toIso8601String(),
            'can_approve' => $this->status === \App\Models\PurchaseRequest::STATUS_QUOTED,
            'can_pay' => $this->status === \App\Models\PurchaseRequest::STATUS_CUSTOMER_APPROVED
                && $this->latestPayment?->status !== 'submitted',
            'latest_payment' => $this->whenLoaded('latestPayment', fn () => $this->latestPayment ? [
                'id' => $this->latestPayment->id,
                'amount_syp' => $this->latestPayment->amount_syp,
                'method' => $this->latestPayment->method,
                'method_label' => $this->latestPayment->methodLabel(),
                'status' => $this->latestPayment->status,
                'submitted_at' => $this->latestPayment->submitted_at?->toIso8601String(),
            ] : null),
            'payment_methods'      => config('payments.methods'),
            'payment_instructions' => config('payments.instructions'),
            'no_receipt_methods'   => config('payments.no_receipt'),
            'store_credit_syp'     => (int) ($request->user()?->store_credit_syp ?? 0),
            'created_at'           => $this->created_at->toIso8601String(),
        ];
    }
}
