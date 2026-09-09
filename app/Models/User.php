<?php

namespace App\Models;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\StoreCreditTransaction;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'store_credit_syp',
        'is_active',
        'avatar',
        'locale',
        'preferred_currency',
        'privacy_settings',
        'google_id',
        'apple_id',
        'phone_verified_at',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'privacy_settings' => 'array',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'apple_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class, 'customer_id');
    }

    public function whatsappGroups()
    {
        return $this->hasMany(WhatsappGroup::class, 'admin_user_id');
    }

    public function storeCreditTransactions()
    {
        return $this->hasMany(StoreCreditTransaction::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /** Deduct store credit and log the transaction. */
    public function debitStoreCredit(int $amountSyp, string $type, ?Model $related = null, ?string $notes = null): StoreCreditTransaction
    {
        if ($this->store_credit_syp < $amountSyp) {
            throw new \RuntimeException('رصيد غير كافٍ.');
        }

        $this->decrement('store_credit_syp', $amountSyp);

        return $this->storeCreditTransactions()->create([
            'amount_syp'   => -$amountSyp,
            'type'         => $type,
            'related_type' => $related ? get_class($related) : null,
            'related_id'   => $related?->getKey(),
            'notes'        => $notes,
        ]);
    }

    /** Add store credit and log the transaction. */
    public function creditStoreCredit(int $amountSyp, string $type, ?Model $related = null, ?string $notes = null): StoreCreditTransaction
    {
        if ($amountSyp < 1) {
            throw new \InvalidArgumentException('مبلغ الرصيد غير صالح.');
        }

        $this->increment('store_credit_syp', $amountSyp);

        return $this->storeCreditTransactions()->create([
            'amount_syp'   => $amountSyp,
            'type'         => $type,
            'related_type' => $related ? get_class($related) : null,
            'related_id'   => $related?->getKey(),
            'notes'        => $notes,
        ]);
    }

    public function productReviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function deviceSightings()
    {
        return $this->hasMany(DeviceSighting::class);
    }

    /**
     * Accounts that shared a physical device with this user (via FCM token hash).
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function siblingAccountsOnDevices()
    {
        $hashes = DeviceSighting::query()
            ->where('user_id', $this->id)
            ->pluck('token_hash');

        if ($hashes->isEmpty()) {
            return collect();
        }

        $userIds = DeviceSighting::query()
            ->whereIn('token_hash', $hashes)
            ->where('user_id', '!=', $this->id)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->get();
    }
}
