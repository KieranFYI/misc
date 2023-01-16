<?php

namespace KieranFYI\Misc\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use KieranFYI\Admin\Services\AdminService;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use KieranFYI\Misc\Services\DebugBar;
use Symfony\Component\HttpFoundation\Response;

class MiscPackageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $root = __DIR__ . '/../..';

        $router->aliasMiddleware('cacheable', CacheableMiddleware::class);

        $this->app->bind('misc-debugbar', DebugBar::class);

        $this->publishes([
            $root . '/config/misc.php' => config_path('misc.php'),
        ], ['misc', 'misc-config', 'config']);

        $this->mergeConfigFrom($root . '/config/misc.php', 'misc');


        if (!app()->runningInConsole()) {
            CacheableMiddleware::checking(function (Response $response) {
                CacheableMiddleware::user($response);
                CacheableMiddleware::cacheView($response);
                CacheableMiddleware::params($response);
            });
        }
    }
}
