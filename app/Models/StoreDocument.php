<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;

class StoreDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'document_type',
        'file_path',
        'status',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Vendor::class, 'store_id');
    }
}
