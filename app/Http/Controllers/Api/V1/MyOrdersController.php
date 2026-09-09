<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Delivery\OrderDeliveryService;
use Illuminate\Http\Request;

class MyOrdersController extends Controller
{
    public function __construct(private OrderDeliveryService $delivery)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $rows = Order::query()
            ->with(['items.product', 'items.platform', 'payment', 'driver', 'deliveryEvents'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (Order $order) {
                $statusKey = $this->delivery->customerStatusKey($order->status);
                $created = $order->placed_at ?? $order->created_at;
                $paymentStatus = $order->payment?->status;
                $filterStatus = $this->filterStatus($statusKey, $paymentStatus);

                return [
                    'key' => 'order-'.$order->id,
                    'type' => 'order',
                    'id' => $order->id,
                    'source' => $order->sourcesLabel(),
                    'source_key' => $order->source_type ?: 'local',
                    'status' => $order->status,
                    'status_label' => $this->delivery->customerStatusLabel($order->status),
                    'status_key' => $statusKey,
                    'filter_group' => $this->filterGroup($statusKey, $paymentStatus),
                    'filter_status' => $filterStatus,
                    'tracking_number' => $order->tracking_number,
                    'total' => (float) $order->total,
                    'total_label' => number_format((float) $order->total, 2).' د.إ',
                    'payment_status' => $paymentStatus,
                    'items' => $order->items->map(function ($i) {
                        $image = $i->product?->image
                            ?: ($i->metadata['image'] ?? null)
                            ?: ($i->metadata['variant_data']['image'] ?? null);

                        return [
                            'name' => $i->displayName(),
                            'quantity' => $i->quantity,
                            'source' => $i->sourceLabel(),
                            'source_type' => $i->source_type,
                            'image' => $image,
                            'product_id' => $i->product_id,
                        ];
                    })->values(),
                    'can_track' => true,
                    'track_url' => '/orders/'.$order->id.'/track',
                    'details_url' => '/orders/'.$order->id.'/track',
                    'invoice_url' => '/orders/'.$order->id.'/invoice',
                    'can_invoice' => true,
                    'can_reorder' => $order->items->contains(fn ($i) => (bool) $i->product_id),
                    'can_cancel' => $this->delivery->canCustomerCancel($order),
                    'cancel_policy' => $this->delivery->customerCancelPolicy($order),
                    'can_review' => $statusKey === 'delivered',
                    'can_approve' => false,
                    'can_pay' => false,
                    'estimated_delivery' => $this->delivery->estimatedDelivery($order),
                    'created_at' => optional($created)->toIso8601String(),
                    'date_label' => $created
                        ? $created->format('Y/m/d')
                        : null,
                    'sort_at' => optional($created)->timestamp ?? $order->id,
                ];
            })
            ->values();

        return response()->json(['data' => $rows]);
    }

    private function filterGroup(string $statusKey, ?string $paymentStatus): string
    {
        if ($paymentStatus === 'refunded') {
            return 'refunded';
        }

        return match ($statusKey) {
            'delivered' => 'completed',
            'cancelled', 'failed_delivery' => 'cancelled',
            'returned' => 'returned',
            default => 'active',
        };
    }

    private function filterStatus(string $statusKey, ?string $paymentStatus): string
    {
        if ($paymentStatus === 'refunded') {
            return 'refunded';
        }

        return match ($statusKey) {
            'pending' => 'pending',
            'preparing' => 'processing',
            'shipped', 'out_for_delivery' => 'shipped',
            'delivered' => 'delivered',
            'cancelled', 'failed_delivery' => 'cancelled',
            'returned' => 'returned',
            default => $statusKey,
        };
    }
}
