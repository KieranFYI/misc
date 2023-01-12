<?php

namespace KieranFYI\Misc\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;

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
    }
}
