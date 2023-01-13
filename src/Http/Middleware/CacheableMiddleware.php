<?php

namespace KieranFYI\Misc\Http\Middleware;

use Carbon\Carbon;
use Closure;
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
     * @return bool
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
                $response = response()
                    ->setCache(self::$options);
            }

            $this->user($response);
            $this->view($response);

            Debugbar::info('Response Modified: ' . ($response->isNotModified($request) ? 'Yes' : 'No'));

            return $response;
        });
    }

    /**
     * @param SymfomyResponse $response
     */
    private function user(SymfomyResponse $response): void
    {
        if (!is_a(Auth::user(), Model::class, true)) {
            return;
        }

        if (!isset(Auth::user()->updated_at)) {
            return;
        }

        Debugbar::debug('User Last Modified: ' . Auth::user()->updated_at);
        $options = ['last_modified' => Auth::user()->updated_at];
        if (!self::check($options)) {
            return;
        }

        Debugbar::debug('Using user last modified');
        $response->setCache($options);
    }

    /**
     * @param SymfomyResponse $response
     */
    private function view(SymfomyResponse $response): void
    {
        Debugbar::measure('CacheMiddleware::view', function () use ($response) {
            $fileTime = Carbon::createFromTimestamp(Cache::remember(static::class, 10, function () {
                Debugbar::debug('Building');
                return $this->bladeFilesIn($this->paths())
                    ->map(function (SplFileInfo $fileInfo) {
                        return $fileInfo->getMTime();
                    })
                    ->max();
            }));

            Debugbar::debug('View Last Modified: ' . $fileTime);
            $options = ['last_modified' => $fileTime];
            if (!self::check($options)) {
                return;
            }

            Debugbar::debug('Using view last modified');
            $response->setCache($options);
        });
    }


    /**
     * Get the Blade files in the given path.
     *
     * @param array $paths
     * @return Collection
     */
    protected function bladeFilesIn(array $paths): Collection
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
    protected function paths(): array
    {
        $finder = View::getFinder();

        return collect($finder->getPaths())->merge(
            collect($finder->getHints())->flatten()
        )
            ->toArray();
    }
}