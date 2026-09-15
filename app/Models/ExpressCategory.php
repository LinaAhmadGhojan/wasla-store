<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpressCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'icon', 'image', 'description', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function stores(): HasMany
    {
        return $this->hasMany(ExpressStore::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(ExpressMenuItem::class);
    }
}
