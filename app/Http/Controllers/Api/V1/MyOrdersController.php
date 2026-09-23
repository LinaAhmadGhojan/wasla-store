<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpressErrandRequest;
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
        $scope = $request->query('scope', 'active');

        $orderRows = Order::query()
            ->with(['items.product', 'items.platform', 'payment', 'driver', 'deliveryEvents', 'shippingAddress'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Order $order) => $this->serializeOrderRow($order));

        $errandRows = ExpressErrandRequest::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (ExpressErrandRequest $row) => $this->serializeErrandRow($row));

        $merged = $orderRows->concat($errandRows)
            ->sortByDesc('sort_at')
            ->values();

        if ($scope === 'active') {
            $merged = $merged->filter(fn (array $row) => $row['is_active'])->values();
        } elseif ($scope === 'history') {
            $merged = $merged->filter(fn (array $row) => ! $row['is_active'])->values();
        }

        return response()->json([
            'data' => $merged,
            'counts' => [
                'active' => $orderRows->concat($errandRows)->filter(fn (array $r) => $r['is_active'])->count(),
                'history' => $orderRows->concat($errandRows)->filter(fn (array $r) => ! $r['is_active'])->count(),
            ],
        ]);
    }

    private function serializeOrderRow(Order $order): array
    {
        $statusKey = $this->delivery->customerStatusKey($order->status);
        $created = $order->placed_at ?? $order->created_at;
        $paymentStatus = $order->payment?->status;
        $filterStatus = $this->filterStatus($statusKey, $paymentStatus);
        $filterGroup = $this->filterGroup($statusKey, $paymentStatus);
        $minutes = $this->delivery->estimatedMinutesRemaining($order);

        return [
            'key' => 'order-'.$order->id,
            'type' => 'order',
            'channel_label' => 'تسوق وصلة',
            'id' => $order->id,
            'source' => $order->sourcesLabel(),
            'source_key' => $order->source_type ?: 'local',
            'status' => $order->status,
            'status_label' => $this->delivery->customerStatusLabel($order->status),
            'status_key' => $statusKey,
            'filter_group' => $filterGroup,
            'filter_status' => $filterStatus,
            'is_active' => $filterGroup === 'active',
            'progress_percent' => $this->delivery->customerProgressPercent($order),
            'minutes_remaining' => $minutes,
            'eta_hint' => $minutes !== null && $minutes > 0
                ? 'تقريباً '.$minutes.' دقيقة'
                : ($statusKey === 'delivered' ? 'تم التوصيل' : null),
            'tracking_number' => $order->tracking_number,
            'total' => (float) $order->total,
            'payment_status' => $paymentStatus,
            'items' => $order->items->map(function ($i) {
                $image = $i->product?->image
                    ?: ($i->metadata['image'] ?? null)
                    ?: ($i->metadata['variant_data']['image'] ?? null);

                return [
                    'name' => $i->displayName(),
                    'quantity' => $i->quantity,
                    'source' => $i->sourceLabel(),
                    'image' => $image,
                ];
            })->values(),
            'can_track' => true,
            'track_url' => '/orders/'.$order->id.'/track',
            'details_url' => '/orders/'.$order->id.'/track',
            'invoice_url' => '/orders/'.$order->id.'/invoice',
            'can_invoice' => $filterGroup !== 'active' || $statusKey === 'delivered',
            'can_reorder' => $order->items->contains(fn ($i) => (bool) $i->product_id),
            'can_cancel' => $this->delivery->canCustomerCancel($order),
            'cancel_policy' => $this->delivery->customerCancelPolicy($order),
            'estimated_delivery' => $this->delivery->estimatedDelivery($order),
            'created_at' => optional($created)->toIso8601String(),
            'date_label' => $created ? $created->format('Y/m/d') : null,
            'sort_at' => optional($created)->timestamp ?? $order->id,
        ];
    }

    private function serializeErrandRow(ExpressErrandRequest $row): array
    {
        $activeStatuses = [
            ExpressErrandRequest::STATUS_PENDING,
            ExpressErrandRequest::STATUS_ACCEPTED,
            ExpressErrandRequest::STATUS_SHOPPING,
        ];
        $isActive = in_array($row->status, $activeStatuses, true);

        $labels = [
            'local_errand' => 'على بابك · مشوار',
            'parcel_receive' => 'على بابك · استلام',
            'parcel_send' => 'على بابك · إرسال',
        ];

        $statusLabels = [
            ExpressErrandRequest::STATUS_PENDING => 'بانتظار التأكيد',
            ExpressErrandRequest::STATUS_ACCEPTED => 'تم التأكيد',
            ExpressErrandRequest::STATUS_SHOPPING => 'قيد التنفيذ',
            ExpressErrandRequest::STATUS_DELIVERED => 'تم',
            ExpressErrandRequest::STATUS_CANCELLED => 'ملغى',
        ];

        $progress = match ($row->status) {
            ExpressErrandRequest::STATUS_PENDING => 15,
            ExpressErrandRequest::STATUS_ACCEPTED => 40,
            ExpressErrandRequest::STATUS_SHOPPING => 70,
            ExpressErrandRequest::STATUS_DELIVERED => 100,
            default => 0,
        };

        $itemPreview = collect($row->items ?? [])->take(3)->pluck('name')->implode(' · ');
        if ($row->parcel_description) {
            $itemPreview = $row->parcel_description;
        }

        return [
            'key' => 'errand-'.$row->id,
            'type' => 'errand',
            'channel_label' => $labels[$row->service_type] ?? 'على بابك',
            'id' => $row->id,
            'reference' => $row->reference,
            'source' => $labels[$row->service_type] ?? 'على بابك',
            'status' => $row->status,
            'status_label' => $statusLabels[$row->status] ?? $row->status,
            'status_key' => $row->status,
            'filter_group' => $isActive ? 'active' : 'completed',
            'filter_status' => $isActive ? 'processing' : 'delivered',
            'is_active' => $isActive,
            'progress_percent' => $progress,
            'minutes_remaining' => $isActive ? 45 : null,
            'eta_hint' => $row->quote_total_syp
                ? 'خدمة: '.number_format((int) $row->quote_total_syp).' ل.س'
                : ($isActive ? 'بانتظار التنفيذ' : null),
            'tracking_number' => $row->reference,
            'total' => $row->quote_total_syp ? (float) $row->quote_total_syp : null,
            'items' => $itemPreview
                ? [['name' => $itemPreview, 'quantity' => 1, 'image' => null]]
                : [],
            'can_track' => false,
            'track_url' => '/express/errand',
            'details_url' => '/express/errand',
            'invoice_url' => null,
            'can_invoice' => false,
            'can_reorder' => false,
            'can_cancel' => false,
            'origin_governorate' => $row->origin_governorate,
            'destination_governorate' => $row->destination_governorate,
            'created_at' => $row->created_at?->toIso8601String(),
            'date_label' => $row->created_at?->format('Y/m/d'),
            'sort_at' => $row->created_at?->timestamp ?? $row->id,
        ];
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
