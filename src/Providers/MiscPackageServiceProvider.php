<?php

namespace KieranFYI\Misc\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use KieranFYI\Misc\Facades\Cacheable;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use KieranFYI\Misc\Services\DebugBar;

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
    }
}
