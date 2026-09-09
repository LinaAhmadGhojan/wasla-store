<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'option_name',
        'option_value',
        'price',
        'sale_price',
        'stock_qty',
        'weight',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $appends = [
        'price_syp',
        'sale_price_syp',
    ];

    public function getPriceSypAttribute(): ?int
    {
        if ($this->price === null) {
            return null;
        }

        $kind = $this->product?->pricingKind() ?? 'product';

        return app(\App\Services\CurrencyService::class)->customerSyp((float) $this->price, $kind);
    }

    public function getSalePriceSypAttribute(): ?int
    {
        if ($this->sale_price === null) {
            return null;
        }

        $kind = $this->product?->pricingKind() ?? 'product';

        return app(\App\Services\CurrencyService::class)->customerSyp((float) $this->sale_price, $kind);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_values');
    }
}
