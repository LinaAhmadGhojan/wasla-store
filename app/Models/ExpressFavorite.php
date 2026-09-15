<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpressFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'express_menu_item_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ExpressMenuItem::class, 'express_menu_item_id');
    }
}
