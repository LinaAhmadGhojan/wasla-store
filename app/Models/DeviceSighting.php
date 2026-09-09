<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSighting extends Model
{
    protected $fillable = [
        'token_hash',
        'user_id',
        'device_name',
        'platform',
        'app',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', trim($token));
    }

    /** Record that this device was seen with this user (keeps history across account switches). */
    public static function record(User $user, string $token, ?string $platform = null, ?string $deviceName = null, string $app = 'customer'): self
    {
        $hash = self::hashToken($token);
        $row = self::query()->firstOrNew([
            'token_hash' => $hash,
            'user_id' => $user->id,
        ]);

        if (! $row->exists) {
            $row->first_seen_at = now();
        }
        $row->device_name = $deviceName ?: $row->device_name;
        $row->platform = $platform ?: $row->platform;
        $row->app = $app ?: ($row->app ?: 'customer');
        $row->last_seen_at = now();
        $row->save();

        return $row;
    }
}
