<?php

namespace KieranFYI\Misc\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Cacheable
{
    public function __construct()
    {
        if (!app()->runningInConsole() || app()->runningUnitTests()) {
            $this->callables[] = function () {
                return Cacheable::user();
            };
            $this->callables[] = function () {
                return Cacheable::cacheView();
            };
            $this->callables[] = function () {
                return Cacheable::params();
            };
        }
    }

    /**
     * @var ?Carbon
     */
    private Carbon|null $timestamp = null;

    /**
     * @var array
     */
    private array $callables = [];

    /**
     * @param Carbon|null $timestamp
     * @return Carbon|null
     */
    public function timestamp(?Carbon $timestamp = null): ?Carbon
    {
        if (func_num_args() !== 0 || is_null($this->timestamp)) {
            if (!is_null($timestamp)) {
                $timestamp = $timestamp->micro(0);
            }
            $this->timestamp = $timestamp;
        }
        return $this->timestamp;
    }

    /**
     * @return array
     */
    public function callables(): array
    {
        return $this->callables;
    }

    /**
     * @param callable $callable
     */
    public function checking(callable $callable): void
    {
        $this->callables[] = $callable;
    }

    /**
     * @param array $options
     * @return bool
     * @throws BindingResolutionException
     */
    public function check(array $options): bool
    {
        $response = response()
            ->make()
            ->setCache($options);
        return $response->isNotModified(Request::createFromGlobals());
    }

    /**
     * @param Carbon|null $value
     * @param bool $throw
     * @return bool
     * @throws BindingResolutionException
     */
    public function cached(Carbon $value = null, bool $throw = true): bool
    {
        if (!config('misc.cache.enabled')) {
            return false;
        }

        $callables = $this->callables();
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
            if (is_null($this->timestamp()) || $value->greaterThan($this->timestamp())) {
                $this->timestamp($value);
            }
        }

        $options = ['last_modified' => $this->timestamp()];
        $response->setCache($options);

        $response = $response->isNotModified($request);
        if ($response && $throw) {
            app('misc-debugbar')->debug('Callable Response not Modified found');
            abort(304);
        }
        return $response;
    }


    /**
     * @return Carbon|null
     */
    public function user(): ?Carbon
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
    public function cacheView(): Carbon
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
    public function params(): ?Carbon
    {
        $updatedAt = $this->checkSignature();
        if (!is_null($updatedAt)) {
            return $updatedAt;
        }

        app('misc-debugbar')->notice('No parameters found, checking middleware');
        return $this->checkMiddleware();
    }

    /**
     * @return Carbon|null
     */
    public function checkSignature(): ?Carbon
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
    public function checkMiddleware(): ?Carbon
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
    protected function bladeFilesIn(array $paths, string $pattern, array $exclude = []): Collection
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
    protected function paths(): array
    {
        $finder = View::getFinder();

        return collect($finder->getPaths())->merge(
            collect($finder->getHints())->flatten()
        )
            ->toArray();
    }
}