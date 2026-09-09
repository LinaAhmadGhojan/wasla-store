<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    public const STATUS_APPROVED = 'approved';

    public const FIT_TOO_SMALL = 'too_small';

    public const FIT_PERFECT = 'perfect';

    public const FIT_TOO_LARGE = 'too_large';

    public const FIT_LABELS = [
        self::FIT_TOO_SMALL => 'ضيق جداً / Too Small',
        self::FIT_PERFECT => 'مناسب / Perfect',
        self::FIT_TOO_LARGE => 'واسع جداً / Too Large',
    ];

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'order_item_id',
        'rating',
        'body',
        'fit_feedback',
        'image_path',
        'image_paths',
        'video_path',
        'reward_syp',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'reward_syp' => 'integer',
        'image_paths' => 'array',
    ];

    protected $appends = [
        'image_url',
        'image_urls',
        'video_url',
        'fit_label',
        'masked_identity',
        'avatar_letter',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        $paths = $this->image_paths ?: [];
        $path = $paths[0] ?? $this->image_path;
        if (! $path) {
            return null;
        }

        return url('storage/'.ltrim(str_replace('\\', '/', (string) $path), '/'));
    }

    public function getImageUrlsAttribute(): array
    {
        $paths = $this->image_paths ?: [];
        if (! $paths && $this->image_path) {
            $paths = [$this->image_path];
        }

        return collect($paths)
            ->map(fn ($path) => url('storage/'.ltrim(str_replace('\\', '/', (string) $path), '/')))
            ->values()
            ->all();
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_path) {
            return null;
        }

        return url('storage/'.ltrim(str_replace('\\', '/', (string) $this->video_path), '/'));
    }

    public function getFitLabelAttribute(): ?string
    {
        if (! $this->fit_feedback) {
            return null;
        }

        return self::FIT_LABELS[$this->fit_feedback] ?? $this->fit_feedback;
    }

    public function getMaskedIdentityAttribute(): string
    {
        $email = $this->user?->email;
        if ($email && str_contains($email, '@')) {
            return self::maskEmail($email);
        }

        $name = trim((string) ($this->user?->name ?: 'عميل'));
        $visible = mb_substr($name, 0, 2);
        $stars = str_repeat('*', max(4, mb_strlen($name) - 2));

        return $visible.$stars;
    }

    public function getAvatarLetterAttribute(): string
    {
        $src = $this->user?->email ?: $this->user?->name ?: 'و';
        $ch = mb_strtoupper(mb_substr((string) $src, 0, 1));

        return $ch !== '' ? $ch : 'و';
    }

    public static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, 'mail.com');
        $local = (string) $local;
        $visible = mb_substr($local, 0, min(5, max(1, mb_strlen($local))));
        $stars = str_repeat('*', 6);

        return $visible.$stars.'@'.$domain;
    }
}
