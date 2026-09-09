<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WishlistList extends Model
{
    protected $table = 'wishlist_lists';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'list_id');
    }

    public static function defaultFor(User $user): self
    {
        $list = static::query()
            ->where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        if ($list) {
            return $list;
        }

        return static::query()->create([
            'user_id' => $user->id,
            'name' => 'مفضلتي',
            'slug' => 'default',
            'is_default' => true,
            'sort_order' => 0,
        ]);
    }
}
