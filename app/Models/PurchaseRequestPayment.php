<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestPayment extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'purchase_request_id',
        'amount_syp',
        'method',
        'status',
        'receipt_path',
        'customer_notes',
        'admin_notes',
        'submitted_at',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'amount_syp' => 'integer',
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function methodLabel(): string
    {
        return config('payments.methods.'.$this->method, $this->method);
    }
}
