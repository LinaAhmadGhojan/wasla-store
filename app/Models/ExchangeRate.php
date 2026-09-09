<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'product_fee_aed',
        'accessory_fee_aed',
        'is_current',
        'notes',
        'set_by',
    ];

    protected $casts = [
        'rate' => 'float',
        'product_fee_aed' => 'float',
        'accessory_fee_aed' => 'float',
        'is_current' => 'boolean',
    ];

    public function setter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    public static function current(): ?self
    {
        return static::query()
            ->where('from_currency', config('currency.procurement', 'AED'))
            ->where('to_currency', config('currency.customer', 'SYP'))
            ->where('is_current', true)
            ->latest('id')
            ->first();
    }
}
