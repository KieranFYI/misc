<?php

namespace KieranFYI\Misc\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use KieranFYI\Misc\Exceptions\CacheableException;
use KieranFYI\Misc\Facades\Debugbar;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Response as SymfomyResponse;

class CacheableMiddleware
{
    /**
     * @var array
     */
    private static array $options = [];

    private static array $callables = [];

    /**
     * @param string $type
     * @param string $value
     */
    public static function set(string $type, mixed $value)
    {
        self::$options[$type] = $value;
        if ($type !== 'cache') {
            app('misc-debugbar')->debug($type . ': ' . $value);
        }
    }

    public static function checking(callable $callable) {
        static::$callables[] = $callable;
    }

    /**
     * @param array|null $options
     * @return bool
     * @throws BindingResolutionException
     */
    public static function check(array $options = null): bool
    {
        if (is_null($options)) {
            $options = self::$options;
        }
        $response = response()
            ->make()
            ->setCache($options);
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

        $response = null;
        try {
            $response = $next($request);
        } catch (CacheableException) {
        }

        return app('misc-debugbar')->measure('CacheableMiddleware', function () use ($request, $next, $response) {
            if (is_null($response)) {
                $response = response();
            }

            if (config('misc.cache')) {
                $options = self::$options;
                unset($options['cache']);
                $response->setCache($options);

                foreach (static::$callables as $callable) {
                    $callable($response);
                }
            }

            app('misc-debugbar')->info('Response Modified: ' . ($response->isNotModified($request) ? 'Yes' : 'No'));

            return $response;
        });
    }

    /**
     * @param SymfomyResponse $response
     */
    public static function cacheView(SymfomyResponse $response): void
    {
        app('misc-debugbar')->measure('CacheMiddleware::view', function () use ($response) {
            $fileTime = Carbon::createFromTimestamp(Cache::remember(self::class, 10, function () {
                return app('misc-debugbar')->measure('Getting File Time', function () {
                    return self::bladeFilesIn(self::paths())
                        ->map(function (SplFileInfo $fileInfo) {
                            return $fileInfo->getMTime();
                        })
                        ->max();
                });
            }));


            if (!isset(self::$options['cache'])) {
                app('misc-debugbar')->debug('View last modified: ' . $fileTime);
            }
            $options = ['last_modified' => $fileTime];

            if (!isset(self::$options['cache'])) {
                if (!self::check($options)) {
                    return;
                }

                app('misc-debugbar')->debug('Using View last modified');
            }

            $response->setCache($options);
        });
    }


    /**
     * Get the Blade files in the given path.
     *
     * @param array $paths
     * @return Collection
     */
    protected static function bladeFilesIn(array $paths): Collection
    {
        return collect(
            Finder::create()
                ->in($paths)
                ->exclude('vendor')
                ->name('*.blade.php')
                ->files()
        )->merge(collect(
            Finder::create()
                ->in(base_path('vendor/composer'))
                ->name('autoload_*.php')
                ->files()));
    }

    /**
     * Get all of the possible view paths.
     *
     * @return array
     */
    protected static function paths(): array
    {
        $finder = View::getFinder();

        return collect($finder->getPaths())->merge(
            collect($finder->getHints())->flatten()
        )
            ->toArray();
    }
}