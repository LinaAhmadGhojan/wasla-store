<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_id',
        'vendor_id',
        'source_type',
        'status',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'discount_amount',
        'total',
        'shipping_address_id',
        'billing_address_id',
        'tracking_number',
        'shipping_method',
        'source_type',
        'delivery_otp',
        'delivery_otp_verified_at',
        'delivery_otp_attempts',
        'signature_path',
        'proof_photo_path',
        'failure_reason',
        'placed_at',
        'delivered_at',
        'canceled_at',
        'cancellation_reason',
        'out_for_delivery_at',
        'failed_at',
        'assigned_at',
        'picked_up_at',
        'customer_notes',
        'is_gift',
        'gift_wrapping',
        'gift_message',
        'gift_recipient_name',
        'gift_recipient_phone',
        'gift_recipient_address',
        'coupon_id',
        'coupon_code',
        'wallet_amount',
        'points_used',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'canceled_at' => 'datetime',
        'delivery_otp_verified_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'failed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'is_gift' => 'boolean',
        'gift_wrapping' => 'boolean',
        'wallet_amount' => 'float',
    ];

    protected $hidden = [
        'delivery_otp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function deliveryEvents()
    {
        return $this->hasMany(DeliveryEvent::class)->orderBy('created_at');
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function sourcesLabel(): string
    {
        $this->loadMissing(['items.platform']);
        $labels = $this->items->map(function ($item) {
            return $item->sourceLabel();
        })->unique()->values();

        if ($labels->isEmpty()) {
            return 'وصلة';
        }

        return $labels->implode(' + ');
    }
}
