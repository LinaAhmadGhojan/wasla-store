<?php

namespace App\Console\Commands;

use App\Services\Catalog\CatalogCache;
use Illuminate\Console\Command;

class CatalogCacheClearCommand extends Command
{
    protected $signature = 'catalog:cache-clear';

    protected $description = 'Invalidate storefront catalog cache (bump content version)';

    public function handle(): int
    {
        CatalogCache::bump();
        $this->info('Catalog cache invalidated. Version is now '.CatalogCache::version().'.');

        return self::SUCCESS;
    }
}
