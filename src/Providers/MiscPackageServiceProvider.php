<?php

namespace KieranFYI\Misc\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use KieranFYI\Admin\Services\AdminService;
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
        $router->aliasMiddleware('cacheable', CacheableMiddleware::class);

        $this->app->bind('misc-debugbar', DebugBar::class);
    }
}
