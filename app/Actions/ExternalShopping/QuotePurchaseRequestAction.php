<?php

namespace App\Actions\ExternalShopping;

use App\Models\PurchaseRequest;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\DB;

class QuotePurchaseRequestAction
{
    public function __construct(private CurrencyService $currency) {}

    public function execute(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($purchaseRequest, $data) {
            $rate = $this->currency->currentAedToSypRate();

            $estimatedAed = isset($data['estimated_price']) ? (float) $data['estimated_price'] : (float) ($purchaseRequest->estimated_price ?? 0);
            $finalAed = isset($data['final_price']) ? (float) $data['final_price'] : (float) ($purchaseRequest->final_price ?? 0);

            $purchaseRequest->update([
                'estimated_price' => $data['estimated_price'] ?? $purchaseRequest->estimated_price,
                'final_price' => $data['final_price'] ?? $purchaseRequest->final_price,
                'estimated_price_syp' => $estimatedAed > 0 ? $this->currency->convertAedToSyp($estimatedAed, $rate) : null,
                'final_price_syp' => $finalAed > 0 ? $this->currency->convertAedToSyp($finalAed, $rate) : null,
                'exchange_rate' => $rate,
                'quoted_at' => now(),
                'admin_notes' => $data['admin_notes'] ?? $purchaseRequest->admin_notes,
                'status' => PurchaseRequest::STATUS_QUOTED,
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                $item = $purchaseRequest->items()->find($itemData['id']);

                if (! $item) {
                    continue;
                }

                $finalPriceAed = isset($itemData['final_price']) ? (float) $itemData['final_price'] : null;

                $item->update(array_filter([
                    'source_price' => $itemData['source_price'] ?? null,
                    'service_fee' => $itemData['service_fee'] ?? null,
                    'shipping_fee' => $itemData['shipping_fee'] ?? null,
                    'final_price' => $itemData['final_price'] ?? null,
                    'final_price_syp' => $finalPriceAed !== null && $finalPriceAed > 0
                        ? $this->currency->convertAedToSyp($finalPriceAed, $rate)
                        : null,
                ], fn ($value) => $value !== null));
            }

            return tap($purchaseRequest->fresh(['items', 'platform', 'customer']), function ($pr) {
                app(\App\Services\Orders\CustomerOrderNotifier::class)->notifyExternalStatus($pr);
            });
        });
    }
}
