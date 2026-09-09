<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncProductCatalogAction
{
    public function execute(Product $product, array $data, array $uploads = [], array $deleteImageIds = []): Product
    {
        return DB::transaction(function () use ($product, $data, $uploads, $deleteImageIds) {
            $product->update($data['product']);

            $this->deleteImages($product, $deleteImageIds);
            $this->syncColorImages($product, $data['colors'] ?? [], $uploads);
            $this->syncVariants($product, $data['colors'] ?? []);
            $this->refreshPrimaryImage($product);

            return $product->fresh(['images', 'variants', 'vendor', 'category', 'brand']);
        });
    }

    /**
     * Save one color (images + sizes) without touching the other colors.
     *
     * @param  array{key?: string, original_key?: string, name: string, hex?: ?string, sizes?: list<array>}  $color
     * @param  list<UploadedFile>  $files
     */
    public function syncOneColor(Product $product, array $color, array $files = [], array $deleteImageIds = []): string
    {
        return DB::transaction(function () use ($product, $color, $files, $deleteImageIds) {
            $this->deleteImages($product, $deleteImageIds);

            $rawOriginal = trim((string) ($color['original_key'] ?? ''));
            $originalKey = $rawOriginal !== ''
                ? $this->normalizeKey($rawOriginal, $rawOriginal)
                : '';
            $colorKey = $this->normalizeKey(
                (string) ($color['key'] ?? ''),
                (string) ($color['name'] ?? 'color')
            );
            $name = trim((string) ($color['name'] ?? '')) ?: $colorKey;
            $color['key'] = $colorKey;
            $color['name'] = $name;

            if ($originalKey !== '' && $originalKey !== $colorKey) {
                $this->renameColorKey($product, $originalKey, $colorKey, $name);
            }

            $this->syncColorImages($product, [$color], [$colorKey => $files]);
            $this->syncVariantsForColor($product, $color, [$originalKey, $colorKey]);
            $this->refreshPrimaryImage($product);

            return $colorKey;
        });
    }

    public function deleteColor(Product $product, string $colorKey): void
    {
        DB::transaction(function () use ($product, $colorKey) {
            $key = $this->normalizeKey($colorKey, $colorKey);
            $this->deleteImages(
                $product,
                $product->images()->where('color_key', $key)->pluck('id')->all()
            );

            foreach ($product->variants()->get() as $variant) {
                $metaKey = (string) (($variant->metadata['color_key'] ?? ''));
                if ($metaKey === $key || $metaKey === $colorKey) {
                    $variant->delete();
                }
            }

            $this->refreshPrimaryImage($product);
        });
    }

    private function deleteImages(Product $product, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $images = $product->images()->whereIn('id', $ids)->get();
        foreach ($images as $image) {
            $this->deletePublicFile($image->path);
            $image->delete();
        }
    }

    /**
     * @param  list<array{key: string, name: string, hex?: ?string}>  $colors
     * @param  array<string, list<UploadedFile>>  $uploads  color_key => files
     */
    private function syncColorImages(Product $product, array $colors, array $uploads): void
    {
        foreach ($colors as $color) {
            $colorKey = $this->normalizeKey($color['key'] ?? '', $color['name'] ?? 'color');
            $name = $color['name'] ?: $colorKey;

            $product->images()
                ->where(function ($query) use ($color, $colorKey) {
                    $query->where('color_key', $color['key'])->orWhere('color_key', $colorKey);
                })
                ->update([
                    'color_key' => $colorKey,
                    'color_name' => $name,
                ]);

            $files = $uploads[$color['key']] ?? $uploads[$colorKey] ?? [];
            $sort = (int) $product->images()->where('color_key', $colorKey)->max('sort_order');

            foreach ($files as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    continue;
                }
                $sort++;
                $path = $this->storeUpload($product, $colorKey, $file);
                ProductImage::create([
                    'product_id' => $product->id,
                    'color_key' => $colorKey,
                    'color_name' => $name,
                    'path' => $path,
                    'sort_order' => $sort,
                    'is_primary' => false,
                ]);
            }
        }
    }

    /**
     * @param  list<array{key: string, name: string, hex?: ?string, sizes?: list<array>}>  $colors
     */
    private function syncVariants(Product $product, array $colors): void
    {
        $keepIds = [];

        foreach ($colors as $color) {
            $colorKey = $this->normalizeKey($color['key'] ?? '', $color['name'] ?? 'color');
            $colorName = $color['name'] ?: $colorKey;
            $hex = $color['hex'] ?? null;
            $images = $product->images()->where('color_key', $colorKey)->orderBy('sort_order')->pluck('path')->all();

            foreach ($color['sizes'] ?? [] as $row) {
                if (! empty($row['_delete'])) {
                    if (! empty($row['id'])) {
                        ProductVariant::where('product_id', $product->id)->where('id', $row['id'])->delete();
                    }
                    continue;
                }

                $sizeKey = $this->normalizeKey($row['size_key'] ?? '', $row['size_name'] ?? 'size');
                $sizeName = $row['size_name'] ?: strtoupper($sizeKey);
                $sku = trim((string) ($row['sku'] ?? '')) ?: strtoupper($colorKey.'-'.$sizeKey.'-'.$product->id);
                $price = (float) ($row['price'] ?? $product->price);
                $stock = (int) ($row['stock_qty'] ?? 0);

                $payload = [
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'option_name' => 'اللون / المقاس',
                    'option_value' => $colorName.' / '.$sizeName,
                    'price' => $price,
                    'sale_price' => null,
                    'stock_qty' => $stock,
                    'metadata' => [
                        'color_key' => $colorKey,
                        'color_name' => $colorName,
                        'color_hex' => $hex,
                        'size_key' => $sizeKey,
                        'size' => $sizeName,
                        'images' => $images,
                    ],
                ];

                $variant = ! empty($row['id'])
                    ? ProductVariant::where('product_id', $product->id)->where('id', $row['id'])->first()
                    : null;

                if ($variant) {
                    try {
                        $variant->update($payload);
                    } catch (\Illuminate\Database\QueryException $e) {
                        $payload['sku'] = $sku.'-'.Str::lower(Str::random(4));
                        $variant->update($payload);
                    }
                    $keepIds[] = $variant->id;
                    continue;
                }

                try {
                    $variant = ProductVariant::create($payload);
                } catch (\Illuminate\Database\QueryException $e) {
                    $payload['sku'] = $sku.'-'.Str::lower(Str::random(4));
                    $variant = ProductVariant::create($payload);
                }
                $keepIds[] = $variant->id;
            }
        }

        $query = $product->variants();
        if ($keepIds !== []) {
            $query->whereNotIn('id', $keepIds)->delete();
        } elseif ($colors !== []) {
            $query->delete();
        }

        $this->touchBasePrice($product);
    }

    /**
     * @param  array{key: string, name: string, hex?: ?string, sizes?: list<array>}  $color
     * @param  list<string>  $ownedKeys
     */
    private function syncVariantsForColor(Product $product, array $color, array $ownedKeys): void
    {
        $keepIds = [];
        $colorKey = $this->normalizeKey($color['key'] ?? '', $color['name'] ?? 'color');
        $colorName = $color['name'] ?: $colorKey;
        $hex = $color['hex'] ?? null;
        $images = $product->images()->where('color_key', $colorKey)->orderBy('sort_order')->pluck('path')->all();
        $ownedKeys = array_values(array_unique(array_filter($ownedKeys)));

        foreach ($color['sizes'] ?? [] as $row) {
            if (! empty($row['_delete'])) {
                if (! empty($row['id'])) {
                    ProductVariant::where('product_id', $product->id)->where('id', $row['id'])->delete();
                }
                continue;
            }

            $variant = $this->upsertVariant($product, $colorKey, $colorName, $hex, $images, $row);
            $keepIds[] = $variant->id;
        }

        foreach ($product->variants()->get() as $variant) {
            $metaKey = (string) ($variant->metadata['color_key'] ?? '');
            if (! in_array($metaKey, $ownedKeys, true) && $metaKey !== $colorKey) {
                continue;
            }
            if (! in_array($variant->id, $keepIds, true)) {
                $variant->delete();
            }
        }

        $this->touchBasePrice($product);
    }

    private function upsertVariant(
        Product $product,
        string $colorKey,
        string $colorName,
        ?string $hex,
        array $images,
        array $row
    ): ProductVariant {
        $sizeKey = $this->normalizeKey($row['size_key'] ?? '', $row['size_name'] ?? 'size');
        $sizeName = $row['size_name'] ?: strtoupper($sizeKey);
        $sku = trim((string) ($row['sku'] ?? '')) ?: strtoupper($colorKey.'-'.$sizeKey.'-'.$product->id);
        $payload = [
            'product_id' => $product->id,
            'sku' => $sku,
            'option_name' => 'اللون / المقاس',
            'option_value' => $colorName.' / '.$sizeName,
            'price' => (float) ($row['price'] ?? $product->price),
            'sale_price' => null,
            'stock_qty' => (int) ($row['stock_qty'] ?? 0),
            'metadata' => [
                'color_key' => $colorKey,
                'color_name' => $colorName,
                'color_hex' => $hex,
                'size_key' => $sizeKey,
                'size' => $sizeName,
                'images' => $images,
            ],
        ];

        $variant = ! empty($row['id'])
            ? ProductVariant::where('product_id', $product->id)->where('id', $row['id'])->first()
            : null;

        try {
            if ($variant) {
                $variant->update($payload);
            } else {
                $variant = ProductVariant::create($payload);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $payload['sku'] = $sku.'-'.Str::lower(Str::random(4));
            if ($variant) {
                $variant->update($payload);
            } else {
                $variant = ProductVariant::create($payload);
            }
        }

        return $variant;
    }

    private function renameColorKey(Product $product, string $from, string $to, string $name): void
    {
        $product->images()->where('color_key', $from)->update([
            'color_key' => $to,
            'color_name' => $name,
        ]);

        foreach ($product->variants()->get() as $variant) {
            $meta = $variant->metadata ?? [];
            if (($meta['color_key'] ?? '') !== $from) {
                continue;
            }
            $meta['color_key'] = $to;
            $meta['color_name'] = $name;
            $variant->update(['metadata' => $meta]);
        }
    }

    private function touchBasePrice(Product $product): void
    {
        $minPrice = $product->variants()->min('price');
        if ($minPrice !== null) {
            $product->update(['price' => $minPrice]);
        }
    }

    private function refreshPrimaryImage(Product $product): void
    {
        $first = $product->images()->orderBy('sort_order')->first();
        $product->images()->update(['is_primary' => false]);
        if ($first) {
            $first->update(['is_primary' => true]);
            $product->update(['image' => $first->path]);
        }
    }

    private function normalizeKey(string $key, string $fallback): string
    {
        $raw = trim($key) !== '' ? $key : $fallback;
        $slug = Str::slug($raw);
        if ($slug !== '') {
            return $slug;
        }

        return 'key-'.substr(md5($raw !== '' ? $raw : uniqid()), 0, 8);
    }

    private function storeUpload(Product $product, string $colorKey, UploadedFile $file): string
    {
        $dir = public_path('products/'.$product->id.'/'.$colorKey);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('تعذر إنشاء مجلد الصور.');
        }

        $name = Str::uuid()->toString().'.'.(strtolower($file->getClientOriginalExtension() ?: 'jpg'));
        $file->move($dir, $name);

        return '/products/'.$product->id.'/'.$colorKey.'/'.$name;
    }

    private function deletePublicFile(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http')) {
            return;
        }

        $absolute = public_path(ltrim($path, '/'));
        if (is_file($absolute) && str_contains(realpath($absolute) ?: $absolute, realpath(public_path('products')) ?: 'products')) {
            @unlink($absolute);
        }
    }
}
