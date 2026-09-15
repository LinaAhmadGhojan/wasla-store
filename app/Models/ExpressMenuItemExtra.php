<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpressMenuItemExtra extends Model
{
    protected $fillable = [
        'express_menu_item_id',
        'group_name',
        'label',
        'price_delta_syp',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_delta_syp' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ExpressMenuItem::class, 'express_menu_item_id');
    }
}
