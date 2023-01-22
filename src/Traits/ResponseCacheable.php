<?php

namespace KieranFYI\Misc\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
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
        if (!config('misc.cache.enabled')) {
            return false;
        }

        $callables = CacheableMiddleware::callables();
        $callables[] = function () use ($value) {
            if (is_null($value)) {
                return null;
            }

            app('misc-debugbar')->debug('User Provided: ' . $value);
            return $value;
        };

        $response = response()
            ->make();
        $request = Request::createFromGlobals();
        foreach ($callables as $callable) {
            $value = $callable();
            if (is_null($value)) {
                continue;
            }
            /** @var Carbon $value */

            $options = ['last_modified' => $value];
            $response->setCache($options);

            if ($response->isNotModified($request)) {
                app('misc-debugbar')->debug('Callable Response not Modified found');
                CacheableMiddleware::$timestamp = $value;
                if ($throw) {
                    abort(304);
                }
                return true;
            }

            if (is_null(CacheableMiddleware::$timestamp) || $value->greaterThan(CacheableMiddleware::$timestamp)) {
                CacheableMiddleware::$timestamp = $value;
            }
        }

        return false;
    }
}