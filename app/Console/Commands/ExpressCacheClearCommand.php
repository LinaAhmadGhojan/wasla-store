<?php

namespace App\Console\Commands;

use App\Services\Express\ExpressCache;
use Illuminate\Console\Command;

class ExpressCacheClearCommand extends Command
{
    protected $signature = 'express:cache-clear';

    protected $description = 'Bump Wasla Express cache version (invalidate browse/facets)';

    public function handle(): int
    {
        ExpressCache::bump();
        $this->info('Express cache version bumped to '.ExpressCache::version());

        return self::SUCCESS;
    }
}
