<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'specs',
        'size_chart',
        'image',
        'video_url',
        'source_platform',
        'source_external_id',
        'source_url',
        'price',
        'sale_price',
        'pricing_type',
        'is_featured',
        'is_active',
        'allow_return',
        'allow_exchange',
        'return_days',
        'exchange_days',
        'whatsapp_published_at',
        'whatsapp_published_target',
        'rating',
        'total_reviews',
        'view_count',
        'sales_count',
        'keywords',
        'is_flash_sale',
        'flash_ends_at',
        'gender',
        'fast_delivery',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_flash_sale' => 'boolean',
        'fast_delivery' => 'boolean',
        'allow_return' => 'boolean',
        'allow_exchange' => 'boolean',
        'whatsapp_published_at' => 'datetime',
        'flash_ends_at' => 'datetime',
        'specs' => 'array',
        'size_chart' => 'array',
    ];

    protected $appends = [
        'price_syp',
        'sale_price_syp',
        'pricing_kind',
        'return_policy_label',
    ];

    public function getReturnPolicyLabelAttribute(): string
    {
        return app(\App\Services\Orders\ReturnExchangePolicy::class)->productPolicy($this)['label'];
    }

    public function pricingKind(): string
    {
        if ($this->pricing_type === 'accessory' || $this->pricing_type === 'product') {
            return $this->pricing_type;
        }

        $slug = strtolower(($this->category?->slug ?? '').' '.($this->category?->parent?->slug ?? ''));

        return str_contains($slug, 'accessor') ? 'accessory' : 'product';
    }

    public function getPricingKindAttribute(): string
    {
        return $this->pricingKind();
    }

    public function getPriceSypAttribute(): ?int
    {
        if ($this->price === null) {
            return null;
        }

        return app(\App\Services\CurrencyService::class)->customerSyp((float) $this->price, $this->pricingKind());
    }

    public function getSalePriceSypAttribute(): ?int
    {
        if ($this->sale_price === null) {
            return null;
        }

        return app(\App\Services\CurrencyService::class)->customerSyp((float) $this->sale_price, $this->pricingKind());
    }

    public function isWhatsappPublished(): bool
    {
        return $this->whatsapp_published_at !== null;
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_product')->withTimestamps();
    }

    public function questions()
    {
        return $this->hasMany(ProductQuestion::class);
    }

    /**
     * Group images + size/price SKUs by color — same structure the storefront shows.
     *
     * @return list<array{key: string, name: string, hex: ?string, images: \Illuminate\Support\Collection, sizes: list<array{variant: ProductVariant, size_key: string, size_name: string}>}>
     */
    public function colorCatalog(): array
    {
        $this->loadMissing(['images', 'variants']);

        $groups = [];

        foreach ($this->images as $image) {
            $key = (string) ($image->color_key ?: 'default');
            $groups[$key] ??= [
                'key' => $key,
                'name' => $image->color_name ?: $key,
                'hex' => null,
                'images' => collect(),
                'sizes' => [],
            ];
            $groups[$key]['images']->push($image);
            if ($image->color_name) {
                $groups[$key]['name'] = $image->color_name;
            }
        }

        foreach ($this->variants as $variant) {
            $meta = $variant->metadata ?? [];
            $key = (string) ($meta['color_key'] ?? '');
            if ($key === '') {
                $key = 'default';
            }
            $groups[$key] ??= [
                'key' => $key,
                'name' => $meta['color_name'] ?? $variant->option_value ?? $key,
                'hex' => $meta['color_hex'] ?? null,
                'images' => collect(),
                'sizes' => [],
            ];
            $groups[$key]['hex'] = $groups[$key]['hex'] ?: ($meta['color_hex'] ?? null);
            $groups[$key]['name'] = $meta['color_name'] ?? $groups[$key]['name'];
            $groups[$key]['sizes'][] = [
                'variant' => $variant,
                'size_key' => (string) ($meta['size_key'] ?? $meta['size'] ?? 'default'),
                'size_name' => (string) ($meta['size'] ?? $meta['size_key'] ?? $variant->option_value ?? '—'),
            ];
        }

        foreach ($groups as &$group) {
            usort($group['sizes'], function ($a, $b) {
                return \App\Support\SizeSorter::compare($a['size_name'], $b['size_name']);
            });
        }
        unset($group);

        return array_values($groups);
    }
}
