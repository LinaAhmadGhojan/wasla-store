<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappBroadcastMessage extends Model
{
    public const CATEGORIES = [
        'orders_open' => 'فتح الطلبية',
        'orders_closed' => 'إغلاق الطلبية',
        'shipping' => 'شحن وتوصيل',
        'motivational' => 'تحفيز وتشجيع',
        'incentive' => 'حوافز ومكافآت',
        'branding' => 'وصلة معكم',
        'welcome' => 'ترحيب',
        'general' => 'عام',
    ];

    protected $fillable = [
        'title',
        'category',
        'body',
        'sort_order',
        'is_active',
        'last_sent_at',
        'send_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'send_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function categoryBadgeClass(): string
    {
        return match ($this->category) {
            'orders_open' => 'bg-success',
            'orders_closed' => 'bg-secondary',
            'shipping' => 'bg-info',
            'motivational' => 'bg-primary',
            'incentive' => 'bg-warning text-dark',
            'branding' => 'bg-dark',
            'welcome' => 'bg-info',
            default => 'bg-light text-dark',
        };
    }
}
