<?php

namespace LaravelEnso\CacheChain\Exceptions;

use Exception;

class Chain extends Exception
{
    public static function adapters(): self
    {
        return new static(__('No cache adapters provided'));
    }
}
