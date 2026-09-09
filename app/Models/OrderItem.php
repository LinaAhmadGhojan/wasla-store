<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    public const SOURCE_LOCAL = 'local';
    public const SOURCE_EXTERNAL = 'external';

    protected $fillable = [
        'order_id',
        'source_type',
        'platform_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'external_url',
        'purchase_request_item_id',
        'quantity',
        'unit_price',
        'line_total',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function platform()
    {
        return $this->belongsTo(ExternalPlatform::class, 'platform_id');
    }

    public function purchaseRequestItem()
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    public function review()
    {
        return $this->hasOne(ProductReview::class);
    }

    public function sourceLabel(): string
    {
        if ($this->source_type === self::SOURCE_EXTERNAL) {
            return $this->platform?->name ?: 'خارجي';
        }

        return 'وصلة';
    }

    public function displayName(): string
    {
        return $this->product_name
            ?: $this->product?->name
            ?: 'منتج';
    }
}
