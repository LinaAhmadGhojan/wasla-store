<?php

namespace App\Support;

/**
 * Local product-only images under public/products/.
 * Flat lay / packshot / hanger — no model photos.
 */
class ProductImageCatalog
{
    /** @var array<string, list<string>> pool key => filenames inside public/products/{pool}/ */
    private const POOLS = [
        'women-clothing' => ['01.jpg', '02.jpg', '03.jpg', '04.jpg', '05.jpg', '06.jpg'],
        'women-accessories-jewelry' => ['01.jpg', '02.jpg', '03.jpg'],
        'women-accessories-watches' => ['01.jpg', '02.jpg', '03.jpg'],
        'women-accessories-scarves' => ['01.jpg', '02.jpg', '03.jpg'],
        'men-clothing' => ['01.jpg', '02.jpg', '03.jpg', '04.jpg'],
        'men-accessories-belts' => ['01.jpg', '02.jpg'],
        'men-accessories-watches' => ['01.jpg', '02.jpg', '03.jpg'],
        'men-accessories-wallets' => ['01.jpg', '02.jpg'],
        'shoes-women' => ['01.jpg', '02.jpg', '03.jpg', '04.jpg'],
        'shoes-men' => ['01.jpg', '02.jpg', '03.jpg'],
        'bags' => ['01.jpg', '02.jpg', '03.jpg', '04.jpg'],
        'beauty' => ['01.jpg', '02.jpg', '03.jpg', '04.jpg', '05.jpg'],
        'home' => ['01.jpg', '02.jpg', '03.jpg', '04.jpg'],
        'electronics' => ['01.jpg', '02.jpg', '03.jpg', '04.jpg'],
        'default' => ['01.jpg', '02.jpg', '03.jpg'],
    ];

    public static function publicRoot(): string
    {
        return public_path('products');
    }

    public static function poolKeyFor(?string $categorySlug): string
    {
        if ($categorySlug) {
            $keys = array_keys(self::POOLS);
            usort($keys, fn (string $a, string $b) => strlen($b) <=> strlen($a));

            foreach ($keys as $key) {
                if ($key === 'default') {
                    continue;
                }

                if (str_starts_with($categorySlug, $key) || str_contains($categorySlug, $key)) {
                    return $key;
                }
            }
        }

        return 'default';
    }

    /** Relative web path, e.g. /products/women-clothing/01.jpg */
    public static function pathFor(?string $categorySlug, int $seed = 1): string
    {
        $poolKey = self::poolKeyFor($categorySlug);
        $files = self::existingFiles($poolKey);
        $index = abs(crc32(($categorySlug ?? 'default') . '-' . $seed)) % count($files);

        return '/products/' . $poolKey . '/' . $files[$index];
    }

    public static function urlFor(?string $categorySlug, int $seed = 1): string
    {
        return self::pathFor($categorySlug, $seed);
    }

    /** @return list<string> filenames that exist on disk for the pool (falls back to default) */
    private static function existingFiles(string $poolKey): array
    {
        $listed = self::POOLS[$poolKey] ?? self::POOLS['default'];
        $dir = self::publicRoot() . DIRECTORY_SEPARATOR . $poolKey;

        $existing = array_values(array_filter(
            $listed,
            fn (string $file) => is_file($dir . DIRECTORY_SEPARATOR . $file) && filesize($dir . DIRECTORY_SEPARATOR . $file) > 5000
        ));

        if ($existing !== []) {
            return $existing;
        }

        if ($poolKey !== 'default') {
            return self::existingFiles('default');
        }

        return $listed;
    }

    /**
     * Pexels product-only photos (no models). Full download URLs.
     *
     * @return array<string, list<string>>
     */
    public static function downloadSources(): array
    {
        $p = fn (int $id) => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w=600&h=600&fit=crop";

        return [
            'women-clothing' => [
                $p(7671166), // folded neutral clothes
                $p(6311392), // dress on hanger
                $p(996329),  // fabric stack
                $p(298346),  // shirt flat lay
                $p(6311652), // rack without faces
                $p(6069122), // modest textile
            ],
            'women-accessories-jewelry' => [
                $p(265906),
                $p(1457827),
                $p(1191531),
            ],
            'women-accessories-watches' => [
                $p(1908195),
                $p(997910),
                $p(278387),
            ],
            'women-accessories-scarves' => [
                $p(6069122),
                $p(7365195),
                $p(994517),
            ],
            'men-clothing' => [
                $p(297933),
                $p(298346),
                $p(7671166),
                $p(6311392),
            ],
            'men-accessories-belts' => [
                $p(1152077),
                $p(1627639),
            ],
            'men-accessories-watches' => [
                $p(1908195),
                $p(997910),
                $p(278387),
            ],
            'men-accessories-wallets' => [
                $p(1627639),
                $p(1152077),
            ],
            'shoes-women' => [
                $p(2529148),
                $p(1464625),
                $p(1598505),
                $p(19090),
            ],
            'shoes-men' => [
                $p(2529148),
                $p(19090),
                $p(1464625),
            ],
            'bags' => [
                $p(909946),
                $p(1152077),
                $p(994523),
                $p(290523),
            ],
            'beauty' => [
                $p(3685530),
                $p(4465127),
                $p(3785147),
                $p(4041392),
                $p(3373736),
            ],
            'home' => [
                $p(1267320),
                $p(259580),
                $p(1571460),
                $p(1454804),
            ],
            'electronics' => [
                $p(788946),
                $p(18105),
                $p(47261),
                $p(163117),
            ],
            'default' => [
                $p(5632401),
                $p(5632399),
                $p(5632402),
            ],
        ];
    }
}
