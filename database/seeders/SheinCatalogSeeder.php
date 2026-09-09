<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Support\SheinCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SheinCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $kids = $this->ensureKidsCategory();
        $brand = Brand::firstOrCreate(
            ['slug' => 'hola-bebe'],
            [
                'name' => 'HolaBebe',
                'description' => 'ملابس أطفال — HolaBebe.',
                'logo' => '/brands/hola-bebe.svg',
                'is_active' => true,
            ]
        );
        $vendor = $this->ensureVendor();

        foreach (SheinCatalog::products() as $catalog) {
            $this->seedProduct($catalog, $vendor, $kids, $brand);
        }
    }

    private function seedProduct(array $catalog, Vendor $vendor, Category $category, Brand $brand): void
    {
        $goodsId = (string) $catalog['goods_id'];
        $slug = 'shein-' . $goodsId;
        $skus = SheinCatalog::expandSkus($catalog);
        $basePrice = (float) ($skus[0]['price'] ?? $catalog['base_price'] ?? 18);

        $product = Product::updateOrCreate(
            ['slug' => $slug],
            [
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'name' => $catalog['name'],
                'description' => $catalog['description'] ?? $catalog['name_en'] ?? null,
                'specs' => $catalog['specs'] ?? null,
                'size_chart' => $catalog['size_chart'] ?? null,
                'price' => $basePrice,
                'sale_price' => null,
                'is_featured' => true,
                'is_active' => true,
                'rating' => 5,
                'total_reviews' => 12,
                'source_platform' => 'shein',
                'source_external_id' => $goodsId,
                'source_url' => $catalog['url'] ?? null,
            ]
        );

        $primaryPath = null;
        $product->images()->delete();
        $product->variants()->delete();

        foreach ($catalog['colors'] ?? [] as $colorIndex => $color) {
            $paths = $this->downloadColorImages($catalog, $color);
            foreach ($paths as $imageIndex => $path) {
                $isPrimary = (($color['key'] ?? '') === ($catalog['default_color'] ?? '') && $imageIndex === 0)
                    || ($colorIndex === 0 && $imageIndex === 0 && empty($catalog['default_color']));
                ProductImage::create([
                    'product_id' => $product->id,
                    'color_key' => $color['key'],
                    'color_name' => $color['name'],
                    'path' => $path,
                    'sort_order' => ($colorIndex * 10) + $imageIndex,
                    'is_primary' => $isPrimary,
                ]);
                if ($isPrimary) {
                    $primaryPath = $path;
                }
            }
        }

        if ($primaryPath) {
            $product->update(['image' => $primaryPath]);
        }

        $sizeNames = collect($catalog['sizes'] ?? [])->keyBy('key');
        $defaultSku = (string) ($catalog['default_sku'] ?? '');
        usort($skus, function ($a, $b) use ($defaultSku) {
            if ($a['sku'] === $defaultSku) {
                return -1;
            }
            if ($b['sku'] === $defaultSku) {
                return 1;
            }

            return 0;
        });

        foreach ($skus as $row) {
            $color = SheinCatalog::colorByKey($catalog, $row['color']);
            $size = $sizeNames->get($row['size']);
            $images = $color ? SheinCatalog::resolvedImages($catalog, $color) : [];
            $colorName = $color['name'] ?? $row['color'];
            $sizeName = $size['name'] ?? $row['size'];

            ProductVariant::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'product_id' => $product->id,
                    'option_name' => 'اللون / المقاس',
                    'option_value' => $colorName . ' / ' . $sizeName,
                    'price' => $row['price'],
                    'sale_price' => null,
                    'stock_qty' => 20,
                    'metadata' => [
                        'color_key' => $row['color'],
                        'color_name' => $colorName,
                        'color_hex' => $color['hex'] ?? null,
                        'size_key' => $row['size'],
                        'size' => $sizeName,
                        'attr_id' => $color['attr_id'] ?? null,
                        'goods_id' => $goodsId,
                        'images' => $images,
                    ],
                ]
            );
        }
    }

    /**
     * @return list<string> public paths
     */
    private function downloadColorImages(array $catalog, array $color): array
    {
        $goodsId = (string) $catalog['goods_id'];
        $colorKey = (string) $color['key'];
        $dir = SheinCatalog::localImageDir($goodsId, $colorKey);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return $color['images'] ?? [];
        }

        $paths = [];
        foreach ($color['images'] ?? [] as $index => $url) {
            if (is_string($url) && str_starts_with($url, '/')) {
                $absolute = public_path(ltrim($url, '/'));
                if (is_file($absolute) && filesize($absolute) > 3000) {
                    $paths[] = $url;
                    continue;
                }
            }

            $relative = SheinCatalog::localImagePath($goodsId, $colorKey, (int) $index);
            $absolute = public_path(ltrim($relative, '/'));

            if (! is_file($absolute) || filesize($absolute) < 3000) {
                try {
                    $response = Http::timeout(45)->withHeaders([
                        'User-Agent' => 'WaslaStore/1.0',
                    ])->get($url);

                    if ($response->successful()
                        && str_starts_with((string) $response->header('Content-Type'), 'image/')
                        && strlen($response->body()) > 3000
                    ) {
                        file_put_contents($absolute, $response->body());
                    }
                } catch (\Throwable $e) {
                    // keep remote URL if download fails
                }
            }

            if (! is_file($absolute) || filesize($absolute) < 3000) {
                $fallback = $this->fallbackLocalImage((int) $index + crc32($colorKey));
                if ($fallback) {
                    copy(public_path(ltrim($fallback, '/')), $absolute);
                }
            }

            $paths[] = (is_file($absolute) && filesize($absolute) > 3000) ? $relative : ($url);
        }

        return $paths;
    }

    private function fallbackLocalImage(int $index): ?string
    {
        $candidates = [
            '/products/women-clothing/01.jpg',
            '/products/women-clothing/02.jpg',
            '/products/women-clothing/03.jpg',
            '/products/women-clothing/04.jpg',
            '/products/default/01.jpg',
        ];
        $path = $candidates[$index % count($candidates)];
        $absolute = public_path(ltrim($path, '/'));

        return (is_file($absolute) && filesize($absolute) > 3000) ? $path : null;
    }

    private function ensureKidsCategory(): Category
    {
        $kids = Category::firstOrCreate(
            ['slug' => 'kids'],
            ['name' => 'أطفال', 'icon' => '👶', 'description' => 'ملابس ومستلزمات الأطفال', 'is_active' => true]
        );

        $clothing = Category::firstOrCreate(
            ['slug' => 'kids-clothing'],
            ['name' => 'ملابس أطفال', 'parent_id' => $kids->id, 'description' => 'ملابس أطفال بنات وأولاد', 'is_active' => true]
        );

        return Category::firstOrCreate(
            ['slug' => 'kids-clothing-dresses'],
            ['name' => 'فساتين بنات', 'parent_id' => $clothing->id, 'description' => 'فساتين وأطقم بناتية', 'is_active' => true]
        );
    }

    private function ensureVendor(): Vendor
    {
        $existing = Vendor::where('slug', 'wasla-fashion-hub')->first()
            ?: Vendor::query()->first();

        if ($existing) {
            return $existing;
        }

        $owner = User::query()->first();

        return Vendor::create([
            'owner_id' => $owner?->id,
            'store_name' => 'Wasla Fashion Hub',
            'slug' => 'wasla-fashion-hub',
            'description' => 'وسيط شراء أزياء من SHEIN والإمارات.',
            'status' => 'active',
            'commission_rate' => 18,
        ]);
    }
}
