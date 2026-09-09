<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappGroup extends Model
{
    protected $fillable = [
        'name',
        'invite_link',
        'whatsapp_chat_id',
        'admin_phone',
        'admin_user_id',
        'message_template',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public static function defaultGroup(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    public function resolvedTemplate(): string
    {
        return trim($this->message_template ?: config('whatsapp.default_template'));
    }
}
