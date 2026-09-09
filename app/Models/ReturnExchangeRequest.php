<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReturnExchangeRequest extends Model
{
    public const TYPE_RETURN = 'return';

    public const TYPE_EXCHANGE = 'exchange';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    public const TRACK_REQUESTED = 'requested';

    public const TRACK_PICKUP_SCHEDULED = 'pickup_scheduled';

    public const TRACK_IN_TRANSIT = 'in_transit';

    public const TRACK_RECEIVED = 'received';

    public const TRACK_APPROVED = 'approved';

    public const TRACK_COMPLETED = 'completed';

    public const TRACK_REJECTED = 'rejected';

    public const REFUND_REQUESTED = 'requested';

    public const REFUND_APPROVED = 'approved';

    public const REFUND_PROCESSING = 'processing';

    public const REFUND_REFUNDED = 'refunded';

    public const REASONS = [
        'wrong_size' => 'مقاس خاطئ',
        'wrong_item' => 'صنف مختلف',
        'damaged' => 'تالف / معيب',
        'not_as_described' => 'غير مطابق للوصف',
        'changed_mind' => 'غيّرت رأيي',
        'other' => 'سبب آخر',
    ];

    protected $fillable = [
        'user_id',
        'order_id',
        'order_item_id',
        'product_id',
        'type',
        'quantity',
        'status',
        'track_status',
        'reason',
        'notes',
        'images',
        'pickup_at',
        'pickup_address',
        'exchange_variant_id',
        'exchange_size_label',
        'from_size_label',
        'deadline_at',
        'refund_status',
        'refund_amount',
        'refund_method',
        'refund_requested_at',
        'refund_approved_at',
        'refund_processing_at',
        'refunded_at',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'pickup_at' => 'datetime',
        'images' => 'array',
        'refund_amount' => 'float',
        'refund_requested_at' => 'datetime',
        'refund_approved_at' => 'datetime',
        'refund_processing_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected $appends = [
        'image_urls',
        'type_label',
        'status_label',
        'track_status_label',
        'reason_label',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function exchangeVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'exchange_variant_id');
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_EXCHANGE ? 'استبدال' : 'إرجاع';
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->typeLabel();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'مقبول',
            self::STATUS_REJECTED => 'مرفوض',
            self::STATUS_COMPLETED => 'مكتمل',
            default => 'قيد المراجعة',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->statusLabel();
    }

    public function trackStatusLabel(): string
    {
        return match ($this->track_status) {
            self::TRACK_PICKUP_SCHEDULED => 'موعد الاستلام محدد',
            self::TRACK_IN_TRANSIT => 'في الطريق للمستودع',
            self::TRACK_RECEIVED => 'وصل للمستودع',
            self::TRACK_APPROVED => 'مقبول',
            self::TRACK_COMPLETED => 'مكتمل',
            self::TRACK_REJECTED => 'مرفوض',
            default => 'تم تقديم الطلب',
        };
    }

    public function getTrackStatusLabelAttribute(): string
    {
        return $this->trackStatusLabel();
    }

    public function getReasonLabelAttribute(): ?string
    {
        if (! $this->reason) {
            return null;
        }

        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($path) => url('storage/'.ltrim(str_replace('\\', '/', (string) $path), '/')))
            ->values()
            ->all();
    }

    public function trackTimeline(): array
    {
        $steps = [
            self::TRACK_REQUESTED => 'تم تقديم الطلب',
            self::TRACK_PICKUP_SCHEDULED => 'جدولة الاستلام',
            self::TRACK_IN_TRANSIT => 'في الطريق',
            self::TRACK_RECEIVED => 'وصل للمستودع',
            self::TRACK_APPROVED => 'تمت الموافقة',
            self::TRACK_COMPLETED => 'مكتمل',
        ];

        if ($this->track_status === self::TRACK_REJECTED || $this->status === self::STATUS_REJECTED) {
            $steps = [
                self::TRACK_REQUESTED => 'تم تقديم الطلب',
                self::TRACK_REJECTED => 'مرفوض',
            ];
        }

        $order = array_keys($steps);
        $current = $this->track_status ?: self::TRACK_REQUESTED;
        $idx = array_search($current, $order, true);
        if ($idx === false) {
            $idx = 0;
        }

        return collect($order)->map(function ($key, $i) use ($idx, $current, $steps) {
            return [
                'key' => $key,
                'label' => $steps[$key],
                'done' => $i < $idx || $current === self::TRACK_COMPLETED,
                'current' => $current === self::TRACK_COMPLETED ? $key === self::TRACK_COMPLETED : $i === $idx,
            ];
        })->all();
    }

    public function refundTimeline(): ?array
    {
        if ($this->type !== self::TYPE_RETURN || ! $this->refund_status) {
            return null;
        }

        $steps = [
            self::REFUND_REQUESTED => 'طلب استرداد',
            self::REFUND_APPROVED => 'موافق عليه',
            self::REFUND_PROCESSING => 'قيد المعالجة',
            self::REFUND_REFUNDED => 'تم الاسترداد',
        ];
        $order = array_keys($steps);
        $current = $this->refund_status;
        $idx = array_search($current, $order, true);
        if ($idx === false) {
            $idx = 0;
        }

        $dates = [
            self::REFUND_REQUESTED => $this->refund_requested_at,
            self::REFUND_APPROVED => $this->refund_approved_at,
            self::REFUND_PROCESSING => $this->refund_processing_at,
            self::REFUND_REFUNDED => $this->refunded_at,
        ];

        return [
            'amount' => (float) ($this->refund_amount ?? 0),
            'method' => $this->refund_method,
            'method_label' => $this->refundMethodLabel(),
            'status' => $this->refund_status,
            'date' => optional($dates[$current] ?? $this->refund_requested_at)->toIso8601String(),
            'steps' => collect($order)->map(function ($key, $i) use ($idx, $current, $steps, $dates) {
                return [
                    'key' => $key,
                    'label' => $steps[$key],
                    'done' => $i < $idx || $current === self::REFUND_REFUNDED,
                    'current' => $current === self::REFUND_REFUNDED ? $key === self::REFUND_REFUNDED : $i === $idx,
                    'at' => optional($dates[$key] ?? null)->toIso8601String(),
                ];
            })->all(),
        ];
    }

    public function refundMethodLabel(): string
    {
        return match ($this->refund_method) {
            'store_credit' => 'رصيد المحفظة',
            'original' => 'نفس طريقة الدفع',
            'bank' => 'تحويل بنكي',
            default => $this->refund_method ?: 'رصيد المحفظة',
        };
    }
}
