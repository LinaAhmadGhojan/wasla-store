<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExpressReview extends Model
{
    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'user_id',
        'express_menu_item_id',
        'express_store_id',
        'rating',
        'body',
        'image_path',
        'reward_syp',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'reward_syp' => 'integer',
    ];

    protected $appends = [
        'image_url',
        'masked_identity',
        'avatar_letter',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ExpressMenuItem::class, 'express_menu_item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(ExpressStore::class, 'express_store_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return str_starts_with($this->image_path, 'http')
            ? $this->image_path
            : url($this->image_path);
    }

    public function getMaskedIdentityAttribute(): string
    {
        $email = (string) ($this->user?->email ?? '');
        if ($email === '') {
            return 'واصِلة';
        }
        $parts = explode('@', $email);
        $local = $parts[0] ?? '';
        $domain = $parts[1] ?? '';
        $maskedLocal = Str::substr($local, 0, 2).str_repeat('*', max(3, strlen($local) - 2));

        return $maskedLocal.'@'.$domain;
    }

    public function getAvatarLetterAttribute(): string
    {
        $name = trim((string) ($this->user?->name ?? 'و'));

        return Str::upper(Str::substr($name, 0, 1)) ?: 'و';
    }
}
