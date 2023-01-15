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
        $callables[] = function (Response $response) use ($value) {
            if (is_null($value)) {
                return;
            }
            $options = ['last_modified' => $value];
            app('misc-debugbar')->debug('User Provided: ' . $value);
            $response->setCache($options);
        };

        $response = response()
            ->make();
        $request = Request::createFromGlobals();
        foreach ($callables as $callable) {
            $callable($response);

            if ($response->isNotModified($request)) {
                app('misc-debugbar')->debug('Callable Response not Modified found');
                CacheableMiddleware::$timestamp = $response->getLastModified();
                abort(304);
            }

            if (is_null(CacheableMiddleware::$timestamp) && CacheableMiddleware::$timestamp < $response->getLastModified()) {
                CacheableMiddleware::$timestamp = $response->getLastModified();
            }
        }
        return true;
    }
}