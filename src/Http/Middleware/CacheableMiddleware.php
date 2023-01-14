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
    public static function checking(callable $callable) {
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

        if (
            is_a($response, SymfomyResponse::class)
            && config('misc.cache')
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
            $response->setCache($options);
        });
    }


    /**
     * @param SymfomyResponse $response
     */
    public static function params(SymfomyResponse $response): void
    {
        $params = collect(request()->route()->signatureParameters(UrlRoutable::class));
        $classes = [];

        $controllerMiddleware = request()->route()->controllerMiddleware();
        foreach ($controllerMiddleware as $middleware) {
            if(!str_starts_with($middleware, 'can:')) {
                continue;
            }
            $class = substr($middleware, strrpos($middleware, ',') + 1);
            if (!class_exists($class)) {
                continue;
            }
            $classes[] = $class;
        }

        foreach ($params as $param) {
            $class = '\\' . $param->getType()->getName();
            if (!class_exists($class)) {
                continue;
            }

            $classes[] = $class;
        }

        /** @var ReflectionParameter $param */
        foreach ($classes as $class) {
            if (!class_exists($class)) {
                continue;
            }
            $model = new $class;
            $updatedAt = Carbon::parse($model->max('updated_at'));

            app('misc-debugbar')->debug($class . ' last modified: ' . $updatedAt);
            $options = ['last_modified' => $updatedAt];
            $response->setCache($options);
            break;
        }
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