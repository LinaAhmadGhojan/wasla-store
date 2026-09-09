<?php

namespace App\Console\Commands;

use App\Services\ImageSearch\VisualSearchService;
use Illuminate\Console\Command;

class IndexProductVisualSearch extends Command
{
    protected $signature = 'products:index-visual
                            {--driver= : local|clip (default from config)}';

    protected $description = 'Build / refresh visual search index (local PHP or CLIP microservice)';

    public function handle(VisualSearchService $search): int
    {
        $driver = $this->option('driver') ?: config('image_search.driver', 'local');

        if ($driver === 'clip') {
            $this->info('Indexing product images via CLIP service…');
            try {
                $stats = $search->rebuildClipIndex(function (int $productId, string $path) {
                    $this->line("  #{$productId}  {$path}");
                });
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->newLine();
            $this->info("Done. sent={$stats['sent']} indexed={$stats['indexed']} errors={$stats['errors']}");

            return self::SUCCESS;
        }

        $this->info('Indexing product images for local visual search…');

        $stats = $search->rebuildLocalIndex(function (int $productId, string $path) {
            $this->line("  #{$productId}  {$path}");
        });

        $this->newLine();
        $this->info("Done. indexed={$stats['indexed']} skipped={$stats['skipped']} errors={$stats['errors']}");

        return self::SUCCESS;
    }
}
