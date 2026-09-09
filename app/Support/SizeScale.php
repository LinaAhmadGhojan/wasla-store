<?php

namespace App\Support;

class SizeScale
{
    /** Kids clothing: 3-month steps, then yearly. */
    public const KIDS = [
        '0-3M', '3-6M', '6-9M', '9-12M',
        '12-15M', '15-18M', '18-21M', '21-24M',
        '2-3Y', '3-4Y', '4-5Y', '5-6Y',
        '6-7Y', '7-8Y', '8-9Y', '9-10Y',
        '10-11Y', '11-12Y', '12-13Y', '13-14Y',
    ];

    /** Women / men clothing. */
    public const LETTERS = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    public const WOMEN_SHOES = ['36', '37', '38', '39', '40', '41', '42'];

    public const MEN_SHOES = ['39', '40', '41', '42', '43', '44', '45', '46'];

    /**
     * @return array{key: string, label: string, sizes: list<string>}
     */
    public static function forSlugs(string $slugs): array
    {
        $s = strtolower($slugs);

        if (str_contains($s, 'kids')) {
            return [
                'key' => 'kids',
                'label' => 'أطفال — كل 3 أشهر ثم سنوي',
                'sizes' => self::KIDS,
            ];
        }

        if (str_contains($s, 'shoe') && (str_contains($s, 'women') || str_contains($s, 'نساء'))) {
            return [
                'key' => 'women_shoes',
                'label' => 'أحذية نسائية',
                'sizes' => self::WOMEN_SHOES,
            ];
        }

        if (str_contains($s, 'shoe') && (str_contains($s, 'men') || str_contains($s, 'رجال'))) {
            return [
                'key' => 'men_shoes',
                'label' => 'أحذية رجالية',
                'sizes' => self::MEN_SHOES,
            ];
        }

        if (str_contains($s, 'shoe')) {
            return [
                'key' => 'women_shoes',
                'label' => 'أحذية',
                'sizes' => self::WOMEN_SHOES,
            ];
        }

        return [
            'key' => 'letters',
            'label' => 'ملابس — XS إلى XXL',
            'sizes' => self::LETTERS,
        ];
    }

    /** @param  list<string>  $existing */
    public static function next(array $existing, array $scale): ?string
    {
        $used = array_map(static fn ($name) => strtoupper(trim((string) $name)), $existing);
        foreach ($scale as $size) {
            if (! in_array(strtoupper($size), $used, true)) {
                return $size;
            }
        }

        return null;
    }
}
