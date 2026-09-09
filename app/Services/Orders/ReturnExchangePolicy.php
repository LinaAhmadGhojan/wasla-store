<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnExchangeRequest;
use App\Models\User;
use App\Services\StoreSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * سياسة متعارف عليها لمتاجر الأزياء/الإلكترونيات المحلية:
 * - المدة من الاستلام (delivered_at) وليس من الطلب
 * - افتراضي 7 أيام إرجاع / 7 أيام استبدال
 * - المنتج يمكن منعه من الإرجاع أو الاستبدال من لوحة التحكم
 */
class ReturnExchangePolicy
{
    public function __construct(private StoreSettings $settings)
    {
    }

    public function defaults(): array
    {
        return [
            'return_days' => max(1, (int) $this->settings->get('return_days_default', 7) ?: 7),
            'exchange_days' => max(1, (int) $this->settings->get('exchange_days_default', 7) ?: 7),
            'from' => 'delivery',
            'reasons' => ReturnExchangeRequest::REASONS,
        ];
    }

    public function productPolicy(?Product $product): array
    {
        $defaults = $this->defaults();
        if (! $product) {
            return [
                'allow_return' => false,
                'allow_exchange' => false,
                'return_days' => $defaults['return_days'],
                'exchange_days' => $defaults['exchange_days'],
                'label' => 'غير قابل للإرجاع أو الاستبدال عبر التطبيق',
            ];
        }

        $returnDays = $product->return_days !== null ? (int) $product->return_days : $defaults['return_days'];
        $exchangeDays = $product->exchange_days !== null ? (int) $product->exchange_days : $defaults['exchange_days'];
        $allowReturn = (bool) ($product->allow_return ?? true);
        $allowExchange = (bool) ($product->allow_exchange ?? true);

        $parts = [];
        if ($allowReturn) {
            $parts[] = "إرجاع خلال {$returnDays} أيام من الاستلام";
        } else {
            $parts[] = 'غير قابل للإرجاع';
        }
        if ($allowExchange) {
            $parts[] = "استبدال خلال {$exchangeDays} أيام من الاستلام";
        } else {
            $parts[] = 'غير قابل للاستبدال';
        }

        return [
            'allow_return' => $allowReturn,
            'allow_exchange' => $allowExchange,
            'return_days' => $returnDays,
            'exchange_days' => $exchangeDays,
            'label' => implode(' · ', $parts),
        ];
    }

    public function eligibility(OrderItem $item, string $type): array
    {
        $item->loadMissing(['order', 'product', 'review', 'variant']);
        $order = $item->order;
        $product = $item->product;
        $policy = $this->productPolicy($product);
        $defaults = $this->defaults();

        $days = $type === ReturnExchangeRequest::TYPE_EXCHANGE
            ? $policy['exchange_days']
            : $policy['return_days'];
        $allowed = $type === ReturnExchangeRequest::TYPE_EXCHANGE
            ? $policy['allow_exchange']
            : $policy['allow_return'];

        $deliveredAt = $order?->delivered_at;
        $deadline = $deliveredAt ? $deliveredAt->copy()->addDays($days) : null;
        $now = now();

        $reasons = [];
        if (! $order || (int) $order->user_id < 1) {
            $reasons[] = 'طلب غير صالح';
        }
        if ($order && $order->status !== 'delivered') {
            $reasons[] = 'يبدأ الإرجاع/الاستبدال بعد استلام الطلبية فقط';
        }
        if (! $item->product_id || ! $product) {
            $reasons[] = 'المنتجات الخارجية غير قابلة للإرجاع/الاستبدال من التطبيق حالياً';
        }
        if (! $allowed) {
            $reasons[] = $type === ReturnExchangeRequest::TYPE_EXCHANGE
                ? 'هذا المنتج غير قابل للاستبدال'
                : 'هذا المنتج غير قابل للإرجاع';
        }
        if ($order && $order->status === 'delivered' && ! $deliveredAt) {
            $reasons[] = 'تاريخ الاستلام غير مسجّل';
        }
        if ($deadline && $now->greaterThan($deadline)) {
            $reasons[] = 'انتهت المدة المسموحة ('.$days.' أيام من الاستلام)';
        }

        $existing = ReturnExchangeRequest::query()
            ->where('order_item_id', $item->id)
            ->where('type', $type)
            ->whereIn('status', [
                ReturnExchangeRequest::STATUS_PENDING,
                ReturnExchangeRequest::STATUS_APPROVED,
                ReturnExchangeRequest::STATUS_COMPLETED,
            ])
            ->exists();
        if ($existing) {
            $reasons[] = 'يوجد طلب '.$type.' مسبقاً لهذا الصنف';
        }

        return [
            'eligible' => $reasons === [],
            'type' => $type,
            'days' => $days,
            'deadline_at' => $deadline?->toIso8601String(),
            'delivered_at' => $deliveredAt?->toIso8601String(),
            'reasons' => $reasons,
            'policy' => $policy,
            'defaults' => $defaults,
            'max_quantity' => max(1, (int) $item->quantity),
            'from_size_label' => $this->sizeLabelFromItem($item),
        ];
    }

    public function createRequest(User $user, OrderItem $item, string $type, array $payload = []): ReturnExchangeRequest
    {
        $item->loadMissing(['order', 'product', 'variant']);
        if ((int) $item->order?->user_id !== (int) $user->id) {
            throw ValidationException::withMessages(['order_item_id' => 'هذا البند ليس لطلبك.']);
        }

        $check = $this->eligibility($item, $type);
        if (! $check['eligible']) {
            throw ValidationException::withMessages([
                'order_item_id' => $check['reasons'][0] ?? 'غير مسموح',
            ]);
        }

        $qty = max(1, min((int) ($payload['quantity'] ?? $item->quantity), (int) $item->quantity));
        $exchangeVariantId = $payload['exchange_variant_id'] ?? null;
        $exchangeSizeLabel = $payload['exchange_size_label'] ?? null;

        if ($type === ReturnExchangeRequest::TYPE_EXCHANGE) {
            if ($exchangeVariantId) {
                $variant = ProductVariant::query()
                    ->where('id', $exchangeVariantId)
                    ->where('product_id', $item->product_id)
                    ->first();
                if (! $variant) {
                    throw ValidationException::withMessages(['exchange_variant_id' => 'المقاس المطلوب غير صالح.']);
                }
                if ((int) $variant->stock_qty <= 0) {
                    throw ValidationException::withMessages(['exchange_variant_id' => 'المقاس المطلوب غير متوفر.']);
                }
                $exchangeSizeLabel = $this->sizeLabelFromVariant($variant);
            } elseif (! $exchangeSizeLabel) {
                throw ValidationException::withMessages(['exchange_size_label' => 'اختاري المقاس الجديد للاستبدال.']);
            }
        }

        $imagePaths = [];
        /** @var list<UploadedFile> $files */
        $files = $payload['images'] ?? [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $imagePaths[] = $file->store('returns', 'public');
            }
        }

        $pickupAt = ! empty($payload['pickup_at']) ? \Carbon\Carbon::parse($payload['pickup_at']) : null;
        $trackStatus = $pickupAt
            ? ReturnExchangeRequest::TRACK_PICKUP_SCHEDULED
            : ReturnExchangeRequest::TRACK_REQUESTED;

        $unit = (float) ($item->unit_price ?: 0);
        $refundAmount = $type === ReturnExchangeRequest::TYPE_RETURN ? round($unit * $qty, 2) : null;

        return ReturnExchangeRequest::query()->create([
            'user_id' => $user->id,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'type' => $type,
            'quantity' => $qty,
            'status' => ReturnExchangeRequest::STATUS_PENDING,
            'track_status' => $trackStatus,
            'reason' => $payload['reason'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'images' => $imagePaths ?: null,
            'pickup_at' => $pickupAt,
            'pickup_address' => $payload['pickup_address'] ?? null,
            'exchange_variant_id' => $exchangeVariantId,
            'exchange_size_label' => $exchangeSizeLabel,
            'from_size_label' => $check['from_size_label'],
            'deadline_at' => $check['deadline_at'] ? \Carbon\Carbon::parse($check['deadline_at']) : null,
            'refund_status' => $type === ReturnExchangeRequest::TYPE_RETURN
                ? ReturnExchangeRequest::REFUND_REQUESTED
                : null,
            'refund_amount' => $refundAmount,
            'refund_method' => $type === ReturnExchangeRequest::TYPE_RETURN
                ? ($payload['refund_method'] ?? 'store_credit')
                : null,
            'refund_requested_at' => $type === ReturnExchangeRequest::TYPE_RETURN ? now() : null,
        ]);
    }

    /** @return list<array> */
    public function eligibleItemsForOrder(Order $order, User $user): array
    {
        if ((int) $order->user_id !== (int) $user->id || $order->status !== 'delivered') {
            return [];
        }

        $out = [];
        foreach ($order->items()->with(['product.variants', 'variant'])->get() as $item) {
            $return = $this->eligibility($item, ReturnExchangeRequest::TYPE_RETURN);
            $exchange = $this->eligibility($item, ReturnExchangeRequest::TYPE_EXCHANGE);
            if (! $return['eligible'] && ! $exchange['eligible']) {
                continue;
            }

            $sizes = [];
            if ($item->product?->relationLoaded('variants') || $item->product) {
                $item->product?->loadMissing('variants');
                foreach ($item->product?->variants ?? [] as $v) {
                    $sizes[] = [
                        'id' => $v->id,
                        'label' => $this->sizeLabelFromVariant($v),
                        'stock_qty' => (int) $v->stock_qty,
                        'available' => (int) $v->stock_qty > 0,
                        'is_current' => (int) $v->id === (int) $item->product_variant_id,
                    ];
                }
            }

            $out[] = [
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->displayName(),
                'image' => $item->product?->image,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'from_size_label' => $this->sizeLabelFromItem($item),
                'exchange_sizes' => $sizes,
                'return' => $return,
                'exchange' => $exchange,
            ];
        }

        return $out;
    }

    public function serialize(ReturnExchangeRequest $r): array
    {
        $r->loadMissing(['product', 'orderItem', 'exchangeVariant']);

        return [
            'id' => $r->id,
            'type' => $r->type,
            'type_label' => $r->typeLabel(),
            'status' => $r->status,
            'status_label' => $r->statusLabel(),
            'track_status' => $r->track_status,
            'track_status_label' => $r->trackStatusLabel(),
            'track_timeline' => $r->trackTimeline(),
            'refund' => $r->refundTimeline(),
            'quantity' => $r->quantity,
            'reason' => $r->reason,
            'reason_label' => $r->reason_label,
            'notes' => $r->notes,
            'images' => $r->image_urls,
            'pickup_at' => optional($r->pickup_at)->toIso8601String(),
            'pickup_address' => $r->pickup_address,
            'from_size_label' => $r->from_size_label,
            'exchange_size_label' => $r->exchange_size_label,
            'exchange_variant_id' => $r->exchange_variant_id,
            'product_name' => $r->product?->name ?: $r->orderItem?->displayName(),
            'order_id' => $r->order_id,
            'created_at' => optional($r->created_at)->toIso8601String(),
            'track_url' => url('/orders/'.$r->order_id.'/track').'#returns',
        ];
    }

    private function sizeLabelFromItem(OrderItem $item): ?string
    {
        if ($item->variant) {
            return $this->sizeLabelFromVariant($item->variant);
        }
        $meta = $item->metadata ?? [];

        return $meta['size_name'] ?? $meta['size'] ?? $meta['variant_data']['size_name'] ?? null;
    }

    private function sizeLabelFromVariant(ProductVariant $variant): string
    {
        $meta = $variant->metadata ?? [];
        if (! empty($meta['size']) || ! empty($meta['size_name'])) {
            $color = $meta['color_name'] ?? null;
            $size = $meta['size_name'] ?? $meta['size'];

            return $color ? "{$color} · {$size}" : (string) $size;
        }

        return trim(($variant->option_name ? $variant->option_name.': ' : '').$variant->option_value);
    }
}
