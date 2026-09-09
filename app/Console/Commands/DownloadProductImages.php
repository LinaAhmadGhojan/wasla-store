<?php

namespace App\Console\Commands;

use App\Support\ProductImageCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DownloadProductImages extends Command
{
    protected $signature = 'products:download-images {--force : Re-download even if file exists}';

    protected $description = 'Download curated product photos into public/products/';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $root = ProductImageCatalog::publicRoot();
        $downloaded = 0;
        $skipped = 0;

        foreach (ProductImageCatalog::downloadSources() as $pool => $photoIds) {
            $dir = $root . DIRECTORY_SEPARATOR . $pool;
            if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                $this->error("Could not create directory: {$dir}");

                return self::FAILURE;
            }

            foreach ($photoIds as $index => $photoId) {
                $filename = sprintf('%02d.jpg', $index + 1);
                $path = $dir . DIRECTORY_SEPARATOR . $filename;

                if (! $force && is_file($path) && filesize($path) > 10_000) {
                    $skipped++;
                    continue;
                }

                $url = is_string($photoId) && str_starts_with($photoId, 'http')
                    ? $photoId
                    : "https://images.unsplash.com/photo-{$photoId}?w=600&h=600&fit=crop&q=85";
                $this->line("Downloading {$pool}/{$filename} …");

                $response = Http::timeout(60)->withHeaders([
                    'User-Agent' => 'WaslaStore/1.0',
                ])->get($url);

                if (! $response->successful()) {
                    $this->warn("Failed: {$pool}/{$filename} (HTTP {$response->status()})");
                    continue;
                }

                file_put_contents($path, $response->body());
                $downloaded++;
            }
        }

        $filled = $this->fillMissingSlots();
        $this->info("Downloaded {$downloaded} image(s), skipped {$skipped} existing, filled {$filled} missing slot(s).");

        return self::SUCCESS;
    }

    private function fillMissingSlots(): int
    {
        $filled = 0;

        foreach (array_keys(ProductImageCatalog::downloadSources()) as $pool) {
            $dir = ProductImageCatalog::publicRoot() . DIRECTORY_SEPARATOR . $pool;
            $files = ProductImageCatalog::downloadSources()[$pool] ?? [];
            $count = count($files);

            $source = null;
            for ($i = 1; $i <= $count; $i++) {
                $candidate = $dir . DIRECTORY_SEPARATOR . sprintf('%02d.jpg', $i);
                if (is_file($candidate) && filesize($candidate) > 5000) {
                    $source = $candidate;
                    break;
                }
            }

            if ($source === null) {
                continue;
            }

            for ($i = 1; $i <= $count; $i++) {
                $target = $dir . DIRECTORY_SEPARATOR . sprintf('%02d.jpg', $i);
                if (! is_file($target) || filesize($target) < 5000) {
                    copy($source, $target);
                    $filled++;
                }
            }
        }

        return $filled;
    }
}
