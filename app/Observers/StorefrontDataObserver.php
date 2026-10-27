<?php

namespace App\Observers;

use App\Support\StorefrontCache;
use Illuminate\Database\Eloquent\Model;

class StorefrontDataObserver
{
    public function saved(Model $model): void
    {
        StorefrontCache::flush();
    }

    public function deleted(Model $model): void
    {
        StorefrontCache::flush();
    }
}
