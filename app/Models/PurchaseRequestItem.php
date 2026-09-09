<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'product_name',
        'external_product_id',
        'variant_data',
        'quantity',
        'source_price',
        'service_fee',
        'shipping_fee',
        'final_price',
        'final_price_syp',
    ];

    protected $casts = [
        'variant_data' => 'array',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function externalProduct()
    {
        return $this->belongsTo(ExternalProduct::class);
    }
}
