<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;

class StoreWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'balance',
        'pending_balance',
        'withdrawn_balance',
        'currency',
        'last_settled_at',
    ];

    protected $casts = [
        'last_settled_at' => 'datetime',
        'balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'withdrawn_balance' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Vendor::class, 'store_id');
    }
}
