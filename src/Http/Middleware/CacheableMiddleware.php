<?php

namespace KieranFYI\Misc\Http\Middleware;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Exception;
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
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @param string|null ...$guards
     * @return Response
     * @throws BindingResolutionException
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        /** @var SymfomyResponse $response */
        try {
            $response = $next($request);
        } catch (HttpException $e) {
            if ($e->getStatusCode() !== 304 || is_null(static::$timestamp)) {
                throw $e;
            }
            return response()->make()->setCache(['last_modified' => static::$timestamp]);
        }

        app('misc-debugbar')->info('Cache: ' . (config('misc.cache.enabled') ? 'Enabled' : 'Disabled'));

        if (
            is_a($response, SymfomyResponse::class)
            && config('misc.cache.enabled')
            && !is_null(static::$timestamp)
        ) {
            app('misc-debugbar')->notice('Setting last modified: ' . static::$timestamp->format('Y-m-d H:i:s'));
            $response->setCache(['last_modified' => static::$timestamp]);
        }
        return $response;
    }

    /**
     * @return Carbon|null
     */
    public static function user(): ?Carbon
    {
        $user = Auth::user();
        if (!is_a($user, Model::class, true)) {
            return null;
        }

        /** @var Carbon $updatedAt */
        $updatedAt = $user->updated_at ?? null;
        app('misc-debugbar')->debug('User last modified: ' . $updatedAt);
        return $updatedAt;
    }

    /**
     * @return Carbon
     */
    public static function cacheView(): Carbon
    {
        return app('misc-debugbar')->measure('CacheMiddleware::view', function () {
            $fileTime = Carbon::createFromTimestamp(Cache::remember(self::class, cache('misc.config.timeout', 0), function () {
                return app('misc-debugbar')->measure('Getting File Time', function () {
                    return
                        self::bladeFilesIn(self::paths(), '*.blade.php', ['vendor'])
                            ->merge(self::bladeFilesIn([base_path('vendor/composer')], 'autoload_*.php'))
                            ->map(function (SplFileInfo $fileInfo) {
                                return $fileInfo->getMTime();
                            })
                            ->max();
                });
            }));


            app('misc-debugbar')->debug('View last modified: ' . $fileTime);
            return $fileTime;
        });
    }


    /**
     * @return Carbon|null
     */
    public static function params(): ?Carbon
    {
        $updatedAt = static::checkSignature();
        if (!is_null($updatedAt)) {
            return $updatedAt;
        }

        app('misc-debugbar')->notice('No parameters found, checking middleware');
        return static::checkMiddleware();
    }

    /**
     * @return Carbon|null
     */
    public static function checkSignature(): ?Carbon
    {
        $route = request()->route();

        if (is_null($route)) {
            return null;
        }

        $updatedAt = null;
        $routeParams = $route->signatureParameters(Model::class);

        /** @var ReflectionParameter $param */
        foreach ($routeParams as $param) {
            $type = $param->getType()->getName();

            /** @var Model $model */
            $model = $type::setEagerLoads([])
                ->select('updated_at')
                ->find($route->parameter($param->name));

            $classUpdatedAt = $model->getAttribute('updated_at');
            app('misc-debugbar')->debug($model::class . ' last modified: ' . $classUpdatedAt);
            if (is_null($updatedAt) || $classUpdatedAt->greaterThan($classUpdatedAt)) {
                $updatedAt = $classUpdatedAt;
            }
        }

        return $updatedAt;
    }

    /**
     * @return Carbon|null
     */
    public static function checkMiddleware(): ?Carbon
    {
        $route = request()->route();
        if (is_null($route)) {
            return null;
        }

        $updatedAt = null;
        $controllerMiddleware = $route->controllerMiddleware();

        foreach ($controllerMiddleware as $middleware) {
            if (!str_starts_with($middleware, 'can:')) {
                continue;
            }
            $class = substr($middleware, strrpos($middleware, ',') + 1);

            $classUpdatedAt = Carbon::parse(Cache::remember($class . '.max', cache('misc.config.timeout', 0), function () use ($class) {
                return $class::max('updated_at');
            }));

            app('misc-debugbar')->debug($class . ' last modified: ' . $classUpdatedAt);
            if (is_null($updatedAt) || $classUpdatedAt->greaterThan($updatedAt)) {
                $updatedAt = $classUpdatedAt;
            }
        }

        return $updatedAt;
    }

    /**
     * Get the Blade files in the given path.
     *
     * @param array $paths
     * @param string $pattern
     * @param array $exclude
     * @return Collection
     */
    protected static function bladeFilesIn(array $paths, string $pattern, array $exclude = []): Collection
    {
        $cleanPaths = [];

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            $cleanPaths[] = $path;
        }

        if (empty($cleanPaths)) {
            return collect();
        }

        return collect(
            Finder::create()
                ->in($cleanPaths)
                ->exclude($exclude)
                ->name($pattern)
                ->files()
        );
    }

    /**
     * Get all the possible view paths.
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