<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpressMenuItemVariant extends Model
{
    protected $fillable = [
        'express_menu_item_id',
        'label',
        'unit_label',
        'price_syp',
        'sale_price_syp',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_syp' => 'integer',
        'sale_price_syp' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ExpressMenuItem::class, 'express_menu_item_id');
    }

    public function getEffectivePriceAttribute(): int
    {
        if ($this->sale_price_syp && $this->sale_price_syp > 0 && $this->sale_price_syp < $this->price_syp) {
            return (int) $this->sale_price_syp;
        }

        return (int) $this->price_syp;
    }
}
