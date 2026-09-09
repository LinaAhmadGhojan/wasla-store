<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImageFeature extends Model
{
    protected $fillable = [
        'product_id',
        'image_path',
        'dhash',
        'color_hist',
    ];

    protected $casts = [
        'color_hist' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
