<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpressMenuItem extends Model
{
    protected $fillable = [
        'express_store_id',
        'express_category_id',
        'name',
        'slug',
        'description',
        'unit_label',
        'serving_note',
        'ingredients',
        'image',
        'price_syp',
        'sale_price_syp',
        'eta_min_minutes',
        'is_offer',
        'is_featured',
        'is_active',
        'sort_order',
        'rating',
        'total_reviews',
    ];

    protected $casts = [
        'price_syp' => 'integer',
        'sale_price_syp' => 'integer',
        'eta_min_minutes' => 'integer',
        'is_offer' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'rating' => 'float',
        'total_reviews' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ExpressStore::class, 'express_store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpressCategory::class, 'express_category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ExpressMenuItemVariant::class)->orderBy('sort_order');
    }

    public function extras(): HasMany
    {
        return $this->hasMany(ExpressMenuItemExtra::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getEffectivePriceAttribute(): int
    {
        if ($this->sale_price_syp && $this->sale_price_syp > 0 && $this->sale_price_syp < $this->price_syp) {
            return (int) $this->sale_price_syp;
        }

        return (int) $this->price_syp;
    }
}
