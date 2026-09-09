<?php

namespace App\Services\ExternalShopping;

use App\Contracts\ExternalCatalogBrowserInterface;
use App\Support\ExternalSearchUrl;
use Illuminate\Support\Str;

/**
 * Demo "browse inside platform" catalog.
 * Same response shape as future SearchAPI/OTAPI adapters — swap driver in config.
 */
class MockExternalCatalogBrowser implements ExternalCatalogBrowserInterface
{
    public function categories(string $platformSlug): array
    {
        $tree = $this->categoryTree($platformSlug);

        return collect($tree)->map(fn ($c) => [
            'id' => $c['id'],
            'name' => $c['name'],
            'name_ar' => $c['name_ar'],
            'image' => $c['image'] ?? null,
            'parent_id' => null,
            'children' => collect($c['children'] ?? [])->map(fn ($child) => [
                'id' => $child['id'],
                'name' => $child['name'],
                'name_ar' => $child['name_ar'],
                'image' => $child['image'] ?? null,
                'parent_id' => $c['id'],
            ])->values()->all(),
        ])->values()->all();
    }

    public function products(string $platformSlug, ?string $categoryId = null, ?string $query = null, int $page = 1): array
    {
        $all = $this->catalog($platformSlug);

        if ($categoryId) {
            $all = array_values(array_filter($all, function ($p) use ($categoryId) {
                return ($p['category_id'] ?? null) === $categoryId
                    || ($p['parent_category_id'] ?? null) === $categoryId;
            }));
        }

        if ($query) {
            $q = Str::lower($query);
            $all = array_values(array_filter($all, function ($p) use ($q) {
                return Str::contains(Str::lower($p['name'] . ' ' . ($p['name_ar'] ?? '')), $q);
            }));
        }

        $perPage = 12;
        $total = count($all);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $slice = array_slice($all, ($page - 1) * $perPage, $perPage);

        return [
            'data' => array_map(fn ($p) => $this->mapListItem($platformSlug, $p), $slice),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'driver' => 'mock',
            ],
        ];
    }

    public function product(string $platformSlug, string $externalProductId): ?array
    {
        foreach ($this->catalog($platformSlug) as $p) {
            if (($p['id'] ?? null) === $externalProductId) {
                return $this->mapDetail($platformSlug, $p);
            }
        }

        return null;
    }

    private function mapListItem(string $platformSlug, array $p): array
    {
        return [
            'id' => $p['id'],
            'platform' => $platformSlug,
            'name' => $p['name'],
            'name_ar' => $p['name_ar'] ?? $p['name'],
            'image' => $p['image'],
            'price' => $p['price'],
            'currency' => 'AED',
            'original_price' => $p['original_price'] ?? null,
            'rating' => $p['rating'] ?? 4.5,
            'category_id' => $p['category_id'],
            // Demo catalog only — there is no real product behind this id, so we
            // point "open on {platform}" at a genuine, working search instead of a fake link.
            'external_url' => ExternalSearchUrl::build($platformSlug, $p['name']),
            'is_demo' => true,
        ];
    }

    private function mapDetail(string $platformSlug, array $p): array
    {
        return array_merge($this->mapListItem($platformSlug, $p), [
            'description' => $p['description'] ?? ($p['name_ar'] . ' — متوفر عبر وصلة من ' . Str::upper($platformSlug)),
            'images' => $p['images'] ?? [$p['image']],
            'variants' => $p['variants'] ?? [
                ['option_name' => 'Size', 'option_value' => 'S'],
                ['option_name' => 'Size', 'option_value' => 'M'],
                ['option_name' => 'Size', 'option_value' => 'L'],
            ],
            'colors' => $p['colors'] ?? ['Black', 'White'],
            'availability' => 'available',
            'source_website' => $this->website($platformSlug),
        ]);
    }

    private function website(string $slug): string
    {
        return match ($slug) {
            'shein' => 'https://www.shein.com',
            'trendyol' => 'https://www.trendyol.com',
            'temu' => 'https://www.temu.com',
            'noon' => 'https://www.noon.com/uae-en',
            'amazon' => 'https://www.amazon.ae',
            default => 'https://example.com',
        };
    }

    private function categoryTree(string $platformSlug): array
    {
        // Same browse UX across platforms; labels can be localized later per store.
        $seed = crc32($platformSlug);

        return [
            [
                'id' => 'women',
                'name' => 'Women',
                'name_ar' => 'نساء',
                'image' => "https://picsum.photos/seed/{$platformSlug}-women/400/400",
                'children' => [
                    ['id' => 'women-dresses', 'name' => 'Dresses', 'name_ar' => 'فساتين', 'image' => "https://picsum.photos/seed/{$platformSlug}-dresses/300/300"],
                    ['id' => 'women-tops', 'name' => 'Tops', 'name_ar' => 'توبات', 'image' => "https://picsum.photos/seed/{$platformSlug}-tops/300/300"],
                    ['id' => 'women-shoes', 'name' => 'Shoes', 'name_ar' => 'أحذية', 'image' => "https://picsum.photos/seed/{$platformSlug}-wshoes/300/300"],
                    ['id' => 'women-bags', 'name' => 'Bags', 'name_ar' => 'حقائب', 'image' => "https://picsum.photos/seed/{$platformSlug}-bags/300/300"],
                ],
            ],
            [
                'id' => 'men',
                'name' => 'Men',
                'name_ar' => 'رجال',
                'image' => "https://picsum.photos/seed/{$platformSlug}-men/400/400",
                'children' => [
                    ['id' => 'men-tshirts', 'name' => 'T-Shirts', 'name_ar' => 'تيشيرتات', 'image' => "https://picsum.photos/seed/{$platformSlug}-tees/300/300"],
                    ['id' => 'men-pants', 'name' => 'Pants', 'name_ar' => 'بناطيل', 'image' => "https://picsum.photos/seed/{$platformSlug}-pants/300/300"],
                    ['id' => 'men-shoes', 'name' => 'Shoes', 'name_ar' => 'أحذية', 'image' => "https://picsum.photos/seed/{$platformSlug}-mshoes/300/300"],
                ],
            ],
            [
                'id' => 'beauty',
                'name' => 'Beauty',
                'name_ar' => 'تجميل',
                'image' => "https://picsum.photos/seed/{$platformSlug}-beauty/400/400",
                'children' => [
                    ['id' => 'beauty-makeup', 'name' => 'Makeup', 'name_ar' => 'مكياج', 'image' => "https://picsum.photos/seed/{$platformSlug}-makeup/300/300"],
                    ['id' => 'beauty-skincare', 'name' => 'Skincare', 'name_ar' => 'عناية', 'image' => "https://picsum.photos/seed/{$platformSlug}-skin/300/300"],
                ],
            ],
            [
                'id' => 'home',
                'name' => 'Home',
                'name_ar' => 'المنزل',
                'image' => "https://picsum.photos/seed/{$platformSlug}-home/400/400",
                'children' => [
                    ['id' => 'home-decor', 'name' => 'Decor', 'name_ar' => 'ديكور', 'image' => "https://picsum.photos/seed/{$platformSlug}-decor/300/300"],
                ],
            ],
        ];
    }

    private function catalog(string $platformSlug): array
    {
        $brand = Str::upper($platformSlug);
        $items = [];

        $defs = [
            ['women-dresses', 'women', 'Summer Floral Dress', 'فستان صيفي منقوش', 79],
            ['women-dresses', 'women', 'Evening Satin Dress', 'فستان سهرة ساتان', 149],
            ['women-dresses', 'women', 'Casual Midi Dress', 'فستان كاجوال ميدي', 89],
            ['women-tops', 'women', 'Linen Blouse', 'بلوزة كتان', 59],
            ['women-tops', 'women', 'Crop Top', 'كروب توب', 39],
            ['women-shoes', 'women', 'White Sneakers', 'سنيكرز أبيض', 129],
            ['women-shoes', 'women', 'Heeled Sandals', 'صندل كعب', 99],
            ['women-bags', 'women', 'Crossbody Bag', 'حقيبة كروس', 119],
            ['women-bags', 'women', 'Mini Shoulder Bag', 'حقيبة كتف صغيرة', 95],
            ['men-tshirts', 'men', 'Basic Cotton Tee', 'تيشيرت قطن أساسي', 45],
            ['men-tshirts', 'men', 'Graphic Oversized Tee', 'تيشيرت أوفر سايز', 55],
            ['men-pants', 'men', 'Slim Fit Jeans', 'جينز سليم', 139],
            ['men-pants', 'men', 'Cargo Pants', 'بنطلون كارغو', 109],
            ['men-shoes', 'men', 'Running Sneakers', 'سنيكرز رياضي', 159],
            ['men-shoes', 'men', 'Leather Loafers', 'حذاء لوفر جلد', 189],
            ['beauty-makeup', 'beauty', 'Matte Lipstick', 'أحمر شفاه مطفي', 35],
            ['beauty-makeup', 'beauty', 'Volume Mascara', 'ماسكارا', 42],
            ['beauty-skincare', 'beauty', 'Vitamin C Serum', 'سيروم فيتامين سي', 68],
            ['home-decor', 'home', 'Scented Candle Set', 'طقم شموع معطرة', 49],
            ['home-decor', 'home', 'Ceramic Vase', 'مزهرية سيراميك', 75],
        ];

        foreach ($defs as $i => [$cat, $parent, $name, $nameAr, $price]) {
            $id = $platformSlug . '-' . ($i + 1);
            $seed = $platformSlug . '-' . ($i + 1);
            $items[] = [
                'id' => $id,
                'category_id' => $cat,
                'parent_category_id' => $parent,
                'name' => "{$brand} {$name}",
                'name_ar' => $nameAr,
                'description' => "{$nameAr} من {$brand} عبر وصلة — توصيل الإمارات.",
                'price' => $price,
                'original_price' => round($price * 1.25, 2),
                'currency' => 'AED',
                'rating' => round(3.8 + (($i % 12) / 10), 1),
                'image' => "https://picsum.photos/seed/{$seed}/600/600",
                'images' => [
                    "https://picsum.photos/seed/{$seed}/600/600",
                    "https://picsum.photos/seed/{$seed}-b/600/600",
                ],
                'variants' => [
                    ['option_name' => 'Size', 'option_value' => 'S'],
                    ['option_name' => 'Size', 'option_value' => 'M'],
                    ['option_name' => 'Size', 'option_value' => 'L'],
                    ['option_name' => 'Size', 'option_value' => 'XL'],
                ],
                'colors' => ['Black', 'Beige', 'White'],
            ];
        }

        return $items;
    }
}
