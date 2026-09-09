<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_id',
        'external_id',
        'external_url',
        'name',
        'description',
        'image',
        'original_price',
        'currency',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function platform()
    {
        return $this->belongsTo(ExternalPlatform::class, 'platform_id');
    }

    public function purchaseRequestItems()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }
}
