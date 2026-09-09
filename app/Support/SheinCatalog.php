<?php

namespace App\Support;

class SheinCatalog
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    public static function path(): string
    {
        return database_path('data/shein-catalog.json');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function products(): array
    {
        if (self::$cache === null) {
            $file = self::path();
            if (! is_file($file)) {
                self::$cache = ['products' => []];
            } else {
                $decoded = json_decode((string) file_get_contents($file), true);
                self::$cache = is_array($decoded) ? $decoded : ['products' => []];
            }
        }

        return self::$cache['products'] ?? [];
    }

    public static function findByGoodsId(?string $goodsId): ?array
    {
        if (! $goodsId) {
            return null;
        }

        foreach (self::products() as $product) {
            if ((string) ($product['goods_id'] ?? '') === (string) $goodsId) {
                return $product;
            }
        }

        return null;
    }

    public static function findBySku(?string $sku): ?array
    {
        if (! $sku) {
            return null;
        }

        foreach (self::products() as $product) {
        foreach (self::expandSkus($product) as $row) {
                if (strcasecmp((string) ($row['sku'] ?? ''), $sku) === 0) {
                    return $product;
                }
            }
        }

        return null;
    }

    public static function findSkuRow(array $product, ?string $sku): ?array
    {
        if (! $sku) {
            return null;
        }

        foreach (self::expandSkus($product) as $row) {
            if (strcasecmp((string) ($row['sku'] ?? ''), $sku) === 0) {
                return $row;
            }
        }

        return null;
    }

    public static function findSkuByColorSize(array $product, string $colorKey, string $sizeKey): ?array
    {
        foreach (self::expandSkus($product) as $row) {
            if (($row['color'] ?? '') === $colorKey && ($row['size'] ?? '') === $sizeKey) {
                return $row;
            }
        }

        return null;
    }

    public static function colorByKey(array $product, string $colorKey): ?array
    {
        foreach ($product['colors'] ?? [] as $color) {
            if (($color['key'] ?? '') === $colorKey) {
                return $color;
            }
        }

        return null;
    }

    public static function colorByAttrId(array $product, ?string $attrId): ?array
    {
        if (! $attrId) {
            return null;
        }

        foreach ($product['colors'] ?? [] as $color) {
            if ((string) ($color['attr_id'] ?? '') === (string) $attrId) {
                return $color;
            }
        }

        return null;
    }

    /**
     * Prefer downloaded local files; fall back to remote catalog URLs.
     *
     * @return list<string>
     */
    public static function resolvedImages(array $product, array $color): array
    {
        $goodsId = (string) ($product['goods_id'] ?? '');
        $colorKey = (string) ($color['key'] ?? '');
        $remote = $color['images'] ?? [];
        $resolved = [];

        foreach ($remote as $index => $url) {
            if (is_string($url) && str_starts_with($url, '/') && is_file(public_path(ltrim($url, '/')))) {
                $resolved[] = $url;
                continue;
            }
            $local = self::localImagePath($goodsId, $colorKey, (int) $index);
            $absolute = public_path(ltrim($local, '/'));
            $resolved[] = (is_file($absolute) && filesize($absolute) > 3000) ? $local : $url;
        }

        return $resolved;
    }

    public static function localImagePath(string $goodsId, string $colorKey, int $index): string
    {
        return '/products/shein/' . $goodsId . '/' . $colorKey . '/' . sprintf('%02d.jpg', $index + 1);
    }

    public static function localImageDir(string $goodsId, string $colorKey): string
    {
        return public_path('products/shein/' . $goodsId . '/' . $colorKey);
    }

    /**
     * Color × size SKUs. Uses catalog.skus when present, otherwise generates from colors/sizes.
     *
     * @return list<array{sku: string, color: string, size: string, price: float}>
     */
    public static function expandSkus(array $product): array
    {
        if (! empty($product['skus']) && is_array($product['skus'])) {
            return $product['skus'];
        }

        $defaultSku = (string) ($product['default_sku'] ?? '');
        $defaultColor = (string) ($product['default_color'] ?? '');
        $defaultSize = (string) ($product['default_size'] ?? '');
        $base = (float) ($product['base_price'] ?? 18.5);
        $out = [];

        foreach (array_values($product['colors'] ?? []) as $colorIndex => $color) {
            foreach (array_values($product['sizes'] ?? []) as $sizeIndex => $size) {
                $colorKey = (string) ($color['key'] ?? '');
                $sizeKey = (string) ($size['key'] ?? '');
                $isDefault = $colorKey === $defaultColor && $sizeKey === $defaultSize && $defaultSku !== '';
                $out[] = [
                    'sku' => $isDefault ? $defaultSku : sprintf('sa25010223485%02d%02d', $colorIndex, $sizeIndex),
                    'color' => $colorKey,
                    'size' => $sizeKey,
                    'price' => round($base + ($sizeIndex * 0.5), 2),
                ];
            }
        }

        return $out;
    }
}
