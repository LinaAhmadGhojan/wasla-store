<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;

class StoreStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'total_orders',
        'total_sales',
        'products_count',
        'reviews_count',
        'average_rating',
        'visitors_last_30d',
    ];

    public function store()
    {
        return $this->belongsTo(Vendor::class, 'store_id');
    }
}
