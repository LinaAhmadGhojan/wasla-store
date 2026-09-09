<?php

namespace App\Services\Whatsapp;

class WhatsappShareImage
{
    public function withPrice(string $sourcePath, string $priceLabel, int $productId): ?string
    {
        if (! is_file($sourcePath) || ! extension_loaded('gd')) {
            return null;
        }

        $src = $this->load($sourcePath);
        if (! $src) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $max = 1280;
        if (max($width, $height) > $max) {
            $scale = $max / max($width, $height);
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($src);
            $src = $resized;
            $width = $newW;
            $height = $newH;
        }

        $barH = (int) max(64, round($height * 0.14));
        imagealphablending($src, true);
        $bar = imagecolorallocate($src, 15, 90, 107);
        imagefilledrectangle($src, 0, $height - $barH, $width, $height, $bar);

        $white = imagecolorallocate($src, 255, 255, 255);
        $font = $this->fontPath();
        $text = $priceLabel;
        $fontSize = max(28, (int) round($width * 0.062));

        if ($font && function_exists('imagettfbbox')) {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $textW = abs($box[2] - $box[0]);
            $textH = abs($box[7] - $box[1]);
            $x = (int) (($width - $textW) / 2);
            $y = (int) ($height - (($barH - $textH) / 2) - 6);
            foreach ([[0, 0], [1, 0], [0, 1], [2, 0], [0, 2], [1, 1]] as $offset) {
                imagettftext($src, $fontSize, 0, max(8, $x + $offset[0]), $y + $offset[1], $white, $font, $text);
            }
        } else {
            $fontId = 5;
            $textW = imagefontwidth($fontId) * strlen($text);
            $x = (int) max(8, ($width - $textW) / 2);
            $y = (int) ($height - $barH + ($barH / 3));
            imagestring($src, $fontId, $x, $y, $text, $white);
        }

        $dir = public_path('whatsapp-cards');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            imagedestroy($src);

            return null;
        }

        $out = $dir.DIRECTORY_SEPARATOR.$productId.'.jpg';
        imagejpeg($src, $out, 88);
        imagedestroy($src);

        return is_file($out) ? $out : null;
    }

    /** @return \GdImage|resource|null */
    private function load(string $path)
    {
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => null,
        };
    }

    private function fontPath(): ?string
    {
        $candidates = [
            'C:\\Windows\\Fonts\\tahomabd.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
            'C:\\Windows\\Fonts\\tahoma.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
