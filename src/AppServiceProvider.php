<?php

namespace LaravelEnso\CacheChain;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use LaravelEnso\CacheChain\Extensions\Chain;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Cache::extend('chain', fn () => Cache::repository(new Chain()));
    }
}
