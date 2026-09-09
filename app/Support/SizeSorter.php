<?php

namespace App\Support;

class SizeSorter
{
    public static function compare(string $a, string $b): int
    {
        $left = self::rank($a);
        $right = self::rank($b);

        return $left <=> $right;
    }

    /** @param  list<string>  $labels */
    public static function sort(array $labels): array
    {
        usort($labels, [self::class, 'compare']);

        return $labels;
    }

    /** @return array{0: int, 1: int, 2: int, 3: string} */
    public static function rank(string $label): array
    {
        $raw = trim($label);
        $key = strtoupper(preg_replace('/\s+/', '', $raw) ?? $raw);

        $letters = [
            'XXXXS' => 1, '3XS' => 1, 'XXS' => 2, '2XS' => 2, 'XS' => 3,
            'S' => 4, 'M' => 5, 'L' => 6, 'XL' => 7, 'XXL' => 8, '2XL' => 8,
            'XXXL' => 9, '3XL' => 9, '4XL' => 10, '5XL' => 11,
            'ONESIZE' => 50, 'OS' => 50, 'FREE' => 50,
        ];
        if (isset($letters[$key])) {
            return [0, $letters[$key], 0, $raw];
        }

        if (preg_match('/(\d+)\s*[-–\/]\s*(\d+)\s*(Y|YEARS?|سنة|سنوات|M|MO|MONTHS?|شهر|شهور)?/iu', $raw, $m)) {
            $unit = strtoupper((string) ($m[3] ?? 'M'));
            $year = str_starts_with($unit, 'Y') || str_contains($unit, 'سنة');
            $mult = $year ? 12 : 1;

            return [1, ((int) $m[1]) * $mult, ((int) $m[2]) * $mult, $raw];
        }

        if (preg_match('/^(\d+(?:\.\d+)?)/', $raw, $m)) {
            return [2, (int) round(((float) $m[1]) * 10), 0, $raw];
        }

        return [9, 0, 0, $raw];
    }
}
