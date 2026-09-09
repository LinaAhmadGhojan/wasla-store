<?php

namespace App\Services\ImageSearch;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Local visual features using PHP GD:
 * - difference hash (structure)
 * - coarse RGB histogram (color)
 */
class LocalFeatureExtractor
{
    public const HIST_BINS = 4; // 4^3 = 64 buckets

    /**
     * @return array{dhash: string, color_hist: list<float>}
     */
    public function extractFromPath(string $absolutePath): array
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException("Image not found: {$absolutePath}");
        }

        $blob = File::get($absolutePath);
        $img = @imagecreatefromstring($blob);
        if ($img === false) {
            throw new RuntimeException("Unsupported or corrupt image: {$absolutePath}");
        }

        try {
            return [
                'dhash' => $this->differenceHash($img),
                'color_hist' => $this->colorHistogram($img),
            ];
        } finally {
            imagedestroy($img);
        }
    }

    /**
     * @return array{dhash: string, color_hist: list<float>}
     */
    public function extractFromUpload(string $tmpPath): array
    {
        return $this->extractFromPath($tmpPath);
    }

    private function differenceHash(\GdImage $src): string
    {
        $w = 9;
        $h = 8;
        $resized = imagecreatetruecolor($w, $h);
        imagecopyresampled($resized, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));

        $bits = '';
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w - 1; $x++) {
                $left = $this->luma(imagecolorat($resized, $x, $y));
                $right = $this->luma(imagecolorat($resized, $x + 1, $y));
                $bits .= $left > $right ? '1' : '0';
            }
        }
        imagedestroy($resized);

        $hex = '';
        foreach (str_split($bits, 4) as $nibble) {
            $hex .= dechex(bindec($nibble));
        }

        return str_pad($hex, 16, '0', STR_PAD_LEFT);
    }

    /**
     * @return list<float>
     */
    private function colorHistogram(\GdImage $src): array
    {
        $size = 32;
        $resized = imagecreatetruecolor($size, $size);
        imagecopyresampled($resized, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));

        $bins = self::HIST_BINS;
        $bucketCount = $bins * $bins * $bins;
        $hist = array_fill(0, $bucketCount, 0.0);
        $total = (float) ($size * $size);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($resized, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $ri = min($bins - 1, intdiv($r * $bins, 256));
                $gi = min($bins - 1, intdiv($g * $bins, 256));
                $bi = min($bins - 1, intdiv($b * $bins, 256));
                $idx = ($ri * $bins * $bins) + ($gi * $bins) + $bi;
                $hist[$idx] += 1.0;
            }
        }
        imagedestroy($resized);

        for ($i = 0; $i < $bucketCount; $i++) {
            $hist[$i] = $hist[$i] / $total;
        }

        return $hist;
    }

    private function luma(int $rgb): float
    {
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        return (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
    }

    public static function hammingDistance(string $a, string $b): int
    {
        $a = str_pad($a, 16, '0', STR_PAD_LEFT);
        $b = str_pad($b, 16, '0', STR_PAD_LEFT);
        $dist = 0;
        for ($i = 0; $i < 16; $i++) {
            $x = hexdec($a[$i]) ^ hexdec($b[$i]);
            $dist += substr_count(decbin($x), '1');
        }

        return $dist;
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 0.0;
        }
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }
        if ($na <= 0.0 || $nb <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }
}
