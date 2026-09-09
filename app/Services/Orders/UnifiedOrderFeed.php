<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\PurchaseRequest;
use App\Services\Delivery\OrderDeliveryService;
use Illuminate\Support\Collection;

class UnifiedOrderFeed
{
    public function forCustomer(int $userId): Collection
    {
        $locals = Order::query()
            ->with(['items.product', 'payment', 'driver'])
            ->where('user_id', $userId)
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Order $o) => $this->mapLocal($o));

        $externals = PurchaseRequest::query()
            ->with(['platform', 'items', 'latestPayment'])
            ->where('customer_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PurchaseRequest $pr) => $this->mapExternal($pr));

        return $locals->concat($externals)
            ->sortByDesc(fn ($row) => $row['sort_at'])
            ->values();
    }

    public function forAdmin(?string $source = null, ?string $status = null): Collection
    {
        $delivery = app(OrderDeliveryService::class);

        $locals = Order::query()
            ->with(['user', 'payment', 'driver', 'items.platform'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (Order $o) use ($delivery) {
                $sources = $o->sourcesLabel();
                $sourceKey = $o->source_type ?: 'local';
                if ($sourceKey === 'mixed') {
                    $sourceKey = 'mixed';
                } elseif ($sourceKey === 'external') {
                    $sourceKey = $o->items->first(fn ($i) => $i->source_type === 'external')?->platform?->slug ?: 'external';
                } else {
                    $sourceKey = 'wasla';
                }

                return [
                    'key' => 'order-'.$o->id,
                    'type' => 'order',
                    'id' => $o->id,
                    'source' => $sources,
                    'source_key' => $sourceKey,
                    'customer_name' => $o->user?->name,
                    'customer_phone' => $o->user?->phone,
                    'status' => $o->status,
                    'status_label' => $delivery->statusLabel($o->status),
                    'amount_label' => number_format((float) $o->total, 2).' د.إ',
                    'sort_at' => optional($o->placed_at ?? $o->created_at)->timestamp ?? 0,
                    'created_at' => optional($o->placed_at ?? $o->created_at)->format('Y-m-d H:i'),
                    'admin_url' => route('admin.orders.show', $o),
                    'track_url' => url('/orders/'.$o->id.'/track'),
                ];
            });

        // Legacy purchase requests not linked to an order (old data only)
        $orphans = PurchaseRequest::query()
            ->with(['customer', 'platform'])
            ->whereNull('order_id')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PurchaseRequest $pr) {
                $sourceName = $pr->platform?->name ?: 'خارجي';

                return [
                    'key' => 'external-'.$pr->id,
                    'type' => 'external',
                    'id' => $pr->id,
                    'source' => $sourceName.' (قديم)',
                    'source_key' => $pr->platform?->slug ?: 'external',
                    'customer_name' => $pr->customer?->name,
                    'customer_phone' => $pr->customer?->phone,
                    'status' => $pr->status,
                    'status_label' => PurchaseRequest::STATUS_LABELS[$pr->status] ?? $pr->status,
                    'amount_label' => $pr->final_price_syp
                        ? number_format((float) $pr->final_price_syp).' ل.س'
                        : '—',
                    'sort_at' => optional($pr->created_at)->timestamp ?? 0,
                    'created_at' => optional($pr->created_at)->format('Y-m-d H:i'),
                    'admin_url' => route('admin.purchase-requests.show', $pr),
                    'track_url' => null,
                ];
            });

        return $locals->concat($orphans)
            ->when($source, function (Collection $rows) use ($source) {
                if ($source === 'wasla') {
                    return $rows->filter(fn ($r) => $r['source_key'] === 'wasla' || str_contains($r['source'], 'وصلة'));
                }

                return $rows->filter(fn ($r) => ($r['source_key'] === $source)
                    || str_contains(mb_strtolower($r['source']), mb_strtolower($source)));
            })
            ->when($status, fn (Collection $rows) => $rows->where('status', $status))
            ->sortByDesc('sort_at')
            ->values();
    }

    private function mapLocal(Order $order): array
    {
        $delivery = app(OrderDeliveryService::class);

        return [
            'key' => 'local-'.$order->id,
            'type' => 'local',
            'id' => $order->id,
            'source' => 'وصلة',
            'source_key' => 'wasla',
            'status' => $order->status,
            'status_label' => $delivery->customerStatusLabel($order->status),
            'status_key' => $delivery->customerStatusKey($order->status),
            'tracking_number' => $order->tracking_number,
            'total_label' => number_format((float) $order->total, 2).' د.إ',
            'items' => collect($order->items)->map(fn ($i) => [
                'name' => $i->product?->name ?? 'منتج',
                'quantity' => $i->quantity,
            ])->values(),
            'track_url' => '/orders/'.$order->id.'/track',
            'can_track' => true,
            'sort_at' => optional($order->placed_at ?? $order->created_at)->toIso8601String(),
            'created_at' => optional($order->placed_at ?? $order->created_at)->toIso8601String(),
        ];
    }

    private function mapExternal(PurchaseRequest $pr): array
    {
        $source = $pr->platform?->name ?: 'خارجي';

        return [
            'key' => 'external-'.$pr->id,
            'type' => 'external',
            'id' => $pr->id,
            'source' => $source,
            'source_key' => $pr->platform?->slug ?: 'external',
            'status' => $pr->status,
            'status_label' => PurchaseRequest::STATUS_LABELS[$pr->status] ?? $pr->status,
            'tracking_number' => null,
            'total_label' => $pr->final_price_syp
                ? number_format((float) $pr->final_price_syp).' ل.س'
                : '—',
            'items' => collect($pr->items)->map(fn ($i) => [
                'name' => $i->product_name,
                'quantity' => $i->quantity,
                'price_syp' => $i->final_price_syp,
            ])->values(),
            'track_url' => null,
            'can_track' => false,
            'can_approve' => $pr->status === PurchaseRequest::STATUS_QUOTED,
            'can_pay' => $pr->status === PurchaseRequest::STATUS_CUSTOMER_APPROVED
                && $pr->latestPayment?->status !== 'submitted',
            'raw' => $pr, // only for server-side if needed — strip in API
            'sort_at' => optional($pr->created_at)->toIso8601String(),
            'created_at' => optional($pr->created_at)->toIso8601String(),
            'platform' => $pr->platform ? ['name' => $pr->platform->name, 'slug' => $pr->platform->slug] : null,
            'final_price_syp' => $pr->final_price_syp,
            'payment_methods' => config('payments.methods'),
            'payment_instructions' => config('payments.instructions'),
            'no_receipt_methods' => config('payments.no_receipt', ['cash_on_delivery', 'store_credit']),
            'store_credit_syp' => (float) ($pr->customer?->store_credit_syp ?? 0),
            'latest_payment' => $pr->latestPayment,
        ];
    }
}
