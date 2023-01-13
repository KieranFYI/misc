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

    /**
     * @param string $type
     * @param string $value
     */
    public static function set(string $type, mixed $value)
    {
        self::$options[$type] = $value;
        Debugbar::debug($type . ': ' . $value);
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
        return Debugbar::measure('CacheableMiddleware', function () use ($request, $next) {
            $response = Debugbar::measure('CacheableMiddleware::$next', function () use ($request, $next) {
                try {
                    return $next($request);
                } catch (CacheableException) {
                }
            });

            if (is_null($response)) {
                $response = response();
            }

            if (config('misc.cache')) {
                $options = self::$options;
                unset($options['cache']);
                $response->setCache($options);
            }

            if (config('misc.cache')) {
                $this->user($response);
                self::cacheView($response);
            }

            Debugbar::info('Response Modified: ' . ($response->isNotModified($request) ? 'Yes' : 'No'));

            return $response;
        });
    }

    /**
     * @param SymfomyResponse $response
     * @throws BindingResolutionException
     */
    private function user(SymfomyResponse $response): void
    {
        $user = Auth::user();
        if (!is_a($user, Model::class, true)) {
            return;
        }

        /** @var Carbon $updatedAt */
        $updatedAt = $user->updated_at ?? null;

        if (method_exists($user, 'load')) {
            if (!$user->relationLoaded('roles')) {
                $user->load('roles');
            }
            if ($user->relationLoaded('roles')) {
                $roleUpdatedAt = $user->roles->max('pivot.updated_at');
                if (is_null($updatedAt) || $updatedAt->lessThan($roleUpdatedAt)) {
                    Debugbar::debug('Using Role updated_at: ' . $roleUpdatedAt);
                    $updatedAt = $roleUpdatedAt;
                }

//                $permissionUpdateAt = $user->roles
//                    ->pluck('permissions')
//                    ->flatten()
//                    ->pluck('pivot.updated_at')
//                    ->max();
//                if (is_null($updatedAt) || $updatedAt->lessThan($permissionUpdateAt)) {
//                    Debugbar::debug('Using Permission updated_at: ' . $permissionUpdateAt);
//                    $updatedAt = $permissionUpdateAt;
//                }
            }
        }

        Debugbar::debug('User Last Modified: ' . $updatedAt);
        $options = ['last_modified' => $updatedAt];
        if (!self::check($options)) {
            return;
        }

        Debugbar::debug('Using user last modified');
        $response->setCache($options);
    }

    /**
     * @param SymfomyResponse $response
     */
    public static function cacheView(SymfomyResponse $response): void
    {
        Debugbar::measure('CacheMiddleware::view', function () use ($response) {
            $fileTime = Carbon::createFromTimestamp(Cache::remember(self::class, 10, function () {
                return Debugbar::measure('Getting File Time', function () {
                    return self::bladeFilesIn(self::paths())
                        ->map(function (SplFileInfo $fileInfo) {
                            return $fileInfo->getMTime();
                        })
                        ->max();
                });
            }));

            Debugbar::debug('View Last Modified: ' . $fileTime);
            $options = ['last_modified' => $fileTime];

            if (!isset(self::$options['cache'])) {
                if (!self::check($options)) {
                    return;
                }

                Debugbar::debug('Using view last modified');
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