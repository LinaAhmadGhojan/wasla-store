<?php

namespace App\Actions\ExternalShopping;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\Orders\CustomerOrderNotifier;
use Illuminate\Support\Facades\DB;

class CreatePurchaseRequestAction
{
    public function execute(User $customer, array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($customer, $data) {
            $purchaseRequest = PurchaseRequest::create([
                'customer_id' => $customer->id,
                'platform_id' => $data['platform_id'] ?? null,
                'url' => $data['url'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'status' => PurchaseRequest::STATUS_PENDING,
            ]);

            foreach ($data['items'] as $item) {
                $purchaseRequest->items()->create([
                    'product_name' => $item['product_name'],
                    'external_product_id' => $item['external_product_id'] ?? null,
                    'variant_data' => $item['variant_data'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            $purchaseRequest = $purchaseRequest->load('items', 'platform', 'customer');
            app(CustomerOrderNotifier::class)->notifyExternalCreated($purchaseRequest);

            return $purchaseRequest;
        });
    }
}
