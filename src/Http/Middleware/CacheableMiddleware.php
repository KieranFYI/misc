<?php

namespace KieranFYI\Misc\Http\Middleware;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use ReflectionParameter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Response as SymfomyResponse;

class CacheableMiddleware
{
    /**
     * @var ?DateTimeInterface
     */
    public static ?DateTimeInterface $timestamp = null;

    /**
     * @var array
     */
    private static array $callables = [];

    /**
     * @return array
     */
    public static function callables(): array
    {
        return static::$callables;
    }

    /**
     * @param callable $callable
     */
    public static function checking(callable $callable)
    {
        static::$callables[] = $callable;
    }

    /**
     * @param array $options
     * @return bool
     * @throws BindingResolutionException
     */
    public static function check(array $options): bool
    {
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
        /** @var SymfomyResponse $response */
        $response = $next($request);

        app('misc-debugbar')->info('Cache: ' . (config('misc.cache.enabled') ? 'Enabled' : 'Disabled'));

        if (
            is_a($response, SymfomyResponse::class)
            && config('misc.cache.enabled')
            && !is_null(static::$timestamp)
        ) {
            app('misc-debugbar')->notice('Setting last modified: ' . static::$timestamp->format('Y-m-d H:i:s'));
            $response->setCache(['last_modified' => static::$timestamp]);

            app('misc-debugbar')->info('Response Modified: ' . ($response->isNotModified($request) ? 'Yes' : 'No'));
            if ($response->getStatusCode() === 304 && $response->isNotModified($request)) {
                $response = response()->make()->setCache(['last_modified' => static::$timestamp]);
            }
        }
        return $response;
    }

    /**
     * @param SymfomyResponse $response
     * @throws BindingResolutionException
     */
    public static function user(SymfomyResponse $response): void
    {
        $user = Auth::user();
        if (!is_a($user, Model::class, true)) {
            return;
        }

        /** @var Carbon $updatedAt */
        $updatedAt = $user->updated_at ?? null;
        app('misc-debugbar')->debug('User last modified: ' . $updatedAt);
        $options = ['last_modified' => $updatedAt];
        $response->setCache($options);
    }

    /**
     * @param SymfomyResponse $response
     */
    public static function cacheView(SymfomyResponse $response): void
    {
        app('misc-debugbar')->measure('CacheMiddleware::view', function () use ($response) {
            $fileTime = Carbon::createFromTimestamp(Cache::remember(self::class, cache('misc.config.timeout', 0), function () {
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
            $response->setCache($options);
        });
    }


    /**
     * @param SymfomyResponse $response
     */
    public static function params(SymfomyResponse $response): void
    {
        $updatedAt = null;

        $routeParams = request()->route()->signatureParameters(UrlRoutable::class);
        /** @var ReflectionParameter $param */
        foreach ($routeParams as $param) {

            /** @var Model $model */
            $model = request()->route()->parameter($param->name);

            try {
                $classUpdatedAt = $model->getAttribute('updated_at');
                app('misc-debugbar')->debug($model::class . ' last modified: ' . $classUpdatedAt);
                if (is_null($updatedAt) || $classUpdatedAt->greaterThan($classUpdatedAt)) {
                    $updatedAt = $classUpdatedAt;
                }
            } catch (Exception) {
            }
        }

        app('misc-debugbar')->notice('No parameters found, checking middleware');

        $controllerMiddleware = request()->route()->controllerMiddleware();
        foreach ($controllerMiddleware as $middleware) {
            if (!str_starts_with($middleware, 'can:')) {
                continue;
            }
            $class = substr($middleware, strrpos($middleware, ',') + 1);
            if (!class_exists($class)) {
                continue;
            }

            $classUpdatedAt = Carbon::parse(Cache::remember($class . '.max', cache('misc.config.timeout', 0), function () use ($class) {
                return $class::max('updated_at');
            }));

            app('misc-debugbar')->debug($class . ' last modified: ' . $classUpdatedAt);
            if (is_null($updatedAt) || $classUpdatedAt->greaterThan($updatedAt)) {
                $updatedAt = $classUpdatedAt;
            }
        }

        if (is_null($updatedAt)) {
            return;
        }

        $options = ['last_modified' => $updatedAt];
        $response->setCache($options);
    }

    /**
     * Get the Blade files in the given path.
     *
     * @param array $paths
     * @return Collection
     */
    protected static function bladeFilesIn(array $paths): Collection
    {
        $cleanPaths = [];

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            $cleanPaths[] = $path;
        }

        return collect(
            Finder::create()
                ->in($cleanPaths)
                ->exclude('vendor')
                ->name('*.blade.php')
                ->files()
        )->merge(collect(
            Finder::create()
                ->in(base_path('vendor/composer'))
                ->name('autoload_*.php')
                ->files()
        ));
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