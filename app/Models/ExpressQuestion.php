<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpressQuestion extends Model
{
    protected $fillable = [
        'express_menu_item_id',
        'user_id',
        'question',
        'answer',
        'answered_at',
        'is_public',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ExpressMenuItem::class, 'express_menu_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
