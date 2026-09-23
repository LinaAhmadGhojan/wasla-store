<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpressErrandRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_SHOPPING = 'shopping';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'reference',
        'service_type',
        'category',
        'items',
        'store_preference',
        'store_name',
        'origin_governorate',
        'origin_details',
        'destination_governorate',
        'parcel_description',
        'parcel_size',
        'delivery_address',
        'contact_phone',
        'customer_note',
        'budget_syp',
        'quote_total_syp',
        'quote_breakdown',
        'shipping_carrier',
        'quote_confirmed_at',
        'urgency',
        'status',
    ];

    protected $casts = [
        'items' => 'array',
        'budget_syp' => 'integer',
        'quote_total_syp' => 'integer',
        'quote_breakdown' => 'array',
        'quote_confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
