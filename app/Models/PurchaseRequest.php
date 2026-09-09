<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_CUSTOMER_APPROVED = 'customer_approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_PURCHASING = 'purchasing';
    public const STATUS_PURCHASED = 'purchased';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_QUOTED,
        self::STATUS_CUSTOMER_APPROVED,
        self::STATUS_PAID,
        self::STATUS_PURCHASING,
        self::STATUS_PURCHASED,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
        self::STATUS_REJECTED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'طلب جديد',
        self::STATUS_UNDER_REVIEW => 'تحت المراجعة',
        self::STATUS_QUOTED => 'عرض سعر',
        self::STATUS_CUSTOMER_APPROVED => 'Order approved',
        self::STATUS_PAID => 'مدفوع',
        self::STATUS_PURCHASING => 'قيد الشراء',
        self::STATUS_PURCHASED => 'تم الشراء',
        self::STATUS_RECEIVED => 'وصل',
        self::STATUS_CANCELLED => 'ملغي',
        self::STATUS_REJECTED => 'مرفوض',
    ];

    protected $fillable = [
        'customer_id',
        'order_id',
        'platform_id',
        'procurement_batch_id',
        'url',
        'status',
        'customer_notes',
        'admin_notes',
        'estimated_price',
        'estimated_price_syp',
        'final_price',
        'final_price_syp',
        'exchange_rate',
        'quoted_at',
        'customer_approved_at',
    ];

    protected $casts = [
        'quoted_at' => 'datetime',
        'customer_approved_at' => 'datetime',
        'exchange_rate' => 'float',
    ];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function platform()
    {
        return $this->belongsTo(ExternalPlatform::class, 'platform_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function procurementBatch()
    {
        return $this->belongsTo(ProcurementBatch::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchaseRequestPayment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(PurchaseRequestPayment::class)->latestOfMany();
    }

    public function canBeAddedToBatch(): bool
    {
        return $this->procurement_batch_id === null
            && in_array($this->status, [
                PurchaseRequest::STATUS_CUSTOMER_APPROVED,
                PurchaseRequest::STATUS_PAID,
            ], true);
    }
}
