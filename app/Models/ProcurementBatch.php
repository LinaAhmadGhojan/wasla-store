<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementBatch extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PURCHASING = 'purchasing';
    public const STATUS_PURCHASED = 'purchased';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_PURCHASING,
        self::STATUS_PURCHASED,
        self::STATUS_SHIPPED,
        self::STATUS_CLOSED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_OPEN => 'مفتوحة — تجمعي طلبات',
        self::STATUS_PURCHASING => 'قيد الشراء',
        self::STATUS_PURCHASED => 'تم الشراء',
        self::STATUS_SHIPPED => 'بالشحن',
        self::STATUS_CLOSED => 'مغلقة',
    ];

    protected $fillable = [
        'reference',
        'title',
        'status',
        'total_aed',
        'external_order_ref',
        'notes',
        'purchased_at',
    ];

    protected $casts = [
        'total_aed' => 'float',
        'purchased_at' => 'datetime',
    ];

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function totalAedFromRequests(): float
    {
        return (float) $this->purchaseRequests()->sum('final_price');
    }

    public static function nextReference(): string
    {
        $date = now()->format('Ymd');
        $count = static::query()->whereDate('created_at', today())->count() + 1;

        return sprintf('BATCH-%s-%03d', $date, $count);
    }
}
