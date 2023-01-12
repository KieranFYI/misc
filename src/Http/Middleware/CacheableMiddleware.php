<?php

namespace KieranFYI\Misc\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use KieranFYI\Misc\Exceptions\CacheableException;

class CacheableMiddleware
{

    /**
     * @var array
     */
    private static array $options = [];

    /**
     * @param string $type
     * @param string $value
     */
    public static function set(string $type, mixed $value)
    {
        self::$options[$type] = $value;
    }

    /**
     * @return bool
     */
    public static function check(): bool
    {
        $response = response()
            ->make()
            ->setCache(self::$options);
        return $response->isNotModified(Request::createFromGlobals());
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param string|null ...$guards
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        try {
            $response = $next($request);

            $response->setCache(self::$options);
            $this->debugBar($response->isNotModified($request));

            return $response;
        } catch (CacheableException) {

        }
        $response = response()
            ->setCache(self::$options);
        $this->debugBar($response->isNotModified($request));

        return $response;
    }

    /**
     * @param bool $modified
     *
     * @return void
     */
    private function debugBar(bool $modified): void
    {
        $debugBar = app('Barryvdh\Debugbar\LaravelDebugbar');
        if (!is_null($debugBar)) {
            $debugBar->info(self::$options);
            $debugBar->info('Modified: ' . $modified);
        }
    }
}