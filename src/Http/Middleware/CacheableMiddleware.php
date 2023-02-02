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
use KieranFYI\Misc\Facades\Cacheable;
use ReflectionParameter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Response as SymfomyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CacheableMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws BindingResolutionException
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var SymfomyResponse $response */
        try {
            $response = $next($request);
        } catch (HttpException $e) {
            if ($e->getStatusCode() !== 304 || is_null(Cacheable::timestamp())) {
                throw $e;
            }
            return response()->make()->setCache(['last_modified' => Cacheable::timestamp()]);
        }

        app('misc-debugbar')->info('Cache: ' . (config('misc.cache.enabled') ? 'Enabled' : 'Disabled'));

        if (
            is_a($response, SymfomyResponse::class)
            && config('misc.cache.enabled')
            && !is_null(Cacheable::timestamp())
        ) {
            app('misc-debugbar')->notice('Setting last modified: ' . Cacheable::timestamp()->format('Y-m-d H:i:s'));
            $response->setCache(['last_modified' => Cacheable::timestamp()]);
        }
        return $response;
    }
}