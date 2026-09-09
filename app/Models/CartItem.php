<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'saved_for_later',
        'price_snapshot',
    ];

    protected $casts = [
        'saved_for_later' => 'boolean',
        'price_snapshot' => 'float',
    ];

    protected $appends = [
        'unit_price_now',
        'price_changed',
        'out_of_stock',
        'low_stock',
        'stock_qty',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function currentUnitPrice(): float
    {
        $source = $this->variant ?: $this->product;
        if (! $source) {
            return 0;
        }

        return (float) ($source->sale_price ?: $source->price);
    }

    public function getUnitPriceNowAttribute(): float
    {
        return $this->currentUnitPrice();
    }

    public function getPriceChangedAttribute(): bool
    {
        if ($this->price_snapshot === null) {
            return false;
        }

        return abs((float) $this->price_snapshot - $this->currentUnitPrice()) > 0.009;
    }

    public function getStockQtyAttribute(): int
    {
        if ($this->variant) {
            return (int) $this->variant->stock_qty;
        }

        return (int) ($this->product?->variants?->sum('stock_qty') ?? 0);
    }

    public function getOutOfStockAttribute(): bool
    {
        if ($this->variant) {
            return (int) $this->variant->stock_qty <= 0;
        }
        if ($this->product && $this->product->relationLoaded('variants') && $this->product->variants->isNotEmpty()) {
            return $this->product->variants->sum('stock_qty') <= 0;
        }

        return false;
    }

    public function getLowStockAttribute(): bool
    {
        $threshold = (int) config('shipping.low_stock_threshold', 3);
        $qty = $this->stock_qty;

        return $qty > 0 && $qty <= $threshold;
    }
}
