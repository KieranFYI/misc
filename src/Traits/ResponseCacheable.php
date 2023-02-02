<?php

namespace KieranFYI\Misc\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
use KieranFYI\Misc\Facades\Cacheable;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

trait ResponseCacheable
{
    /**
     * @throws Throwable
     */
    private function cached(Carbon $value = null, bool $throw = true): bool
    {
        return Cacheable::cached($value, $throw);
    }
}