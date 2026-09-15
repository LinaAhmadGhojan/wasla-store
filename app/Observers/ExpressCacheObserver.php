<?php

namespace App\Observers;

use App\Services\Express\ExpressCache;

class ExpressCacheObserver
{
    public function created(mixed $model): void
    {
        ExpressCache::bump();
    }

    public function updated(mixed $model): void
    {
        ExpressCache::bump();
    }

    public function deleted(mixed $model): void
    {
        ExpressCache::bump();
    }

    public function restored(mixed $model): void
    {
        ExpressCache::bump();
    }
}
