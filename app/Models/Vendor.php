<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\City;
use App\Models\Country;
use App\Models\StoreDocument;
use App\Models\StoreStatistic;
use App\Models\StoreWallet;
use App\Models\StoreWorkingHour;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'stores';

    protected $fillable = [
        'owner_id',
        'store_name',
        'slug',
        'legal_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'description',
        'logo',
        'banner',
        'country_id',
        'city_id',
        'area',
        'street_address',
        'postal_code',
        'commission_rate',
        'delivery_fee',
        'return_policy',
        'status',
        'is_featured',
        'is_verified',
        'is_open',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'is_open' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function documents()
    {
        return $this->hasMany(StoreDocument::class, 'store_id');
    }

    public function workingHours()
    {
        return $this->hasMany(StoreWorkingHour::class, 'store_id');
    }

    public function statistics()
    {
        return $this->hasOne(StoreStatistic::class, 'store_id');
    }

    public function wallet()
    {
        return $this->hasOne(StoreWallet::class, 'store_id');
    }
}
