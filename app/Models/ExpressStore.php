<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpressStore extends Model
{
    protected $fillable = [
        'owner_id',
        'express_category_id',
        'store_name',
        'slug',
        'cuisine',
        'description',
        'logo',
        'banner',
        'area',
        'street_address',
        'delivery_fee_syp',
        'eta_min_minutes',
        'eta_max_minutes',
        'rating',
        'total_reviews',
        'commission_rate',
        'status',
        'is_featured',
        'is_verified',
        'is_open',
    ];

    protected $casts = [
        'delivery_fee_syp' => 'integer',
        'eta_min_minutes' => 'integer',
        'eta_max_minutes' => 'integer',
        'rating' => 'float',
        'total_reviews' => 'integer',
        'commission_rate' => 'float',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'is_open' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpressCategory::class, 'express_category_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(ExpressMenuItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_open', true);
    }

    public function getEtaLabelAttribute(): string
    {
        return $this->eta_min_minutes.'–'.$this->eta_max_minutes.' د';
    }
}
