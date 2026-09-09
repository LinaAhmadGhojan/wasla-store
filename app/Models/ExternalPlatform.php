<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalPlatform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'website',
        'type',
        'status',
        'currency',
        'country',
        'commission_rate',
        'markup_rate',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'markup_rate' => 'decimal:2',
    ];

    public function externalProducts()
    {
        return $this->hasMany(ExternalProduct::class, 'platform_id');
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class, 'platform_id');
    }
}
