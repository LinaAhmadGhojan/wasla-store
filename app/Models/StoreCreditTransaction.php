<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StoreCreditTransaction extends Model
{
    public const TYPE_TOP_UP    = 'manual_top_up';
    public const TYPE_PAYMENT   = 'payment_debit';
    public const TYPE_REFUND    = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_REVIEW_REWARD = 'review_reward';

    protected $fillable = [
        'user_id',
        'amount_syp',
        'type',
        'related_type',
        'related_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount_syp' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
