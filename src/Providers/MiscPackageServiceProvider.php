<?php

namespace KieranFYI\Misc\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
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
        ], ['misc', 'misc-config']);

        $this->mergeConfigFrom($root . '/config/misc.php', 'misc');


        if (!app()->runningInConsole()) {
            CacheableMiddleware::checking(function (Response $response) {
                $user = Auth::user();
                if (!is_a($user, Model::class, true)) {
                    return;
                }

                /** @var Carbon $updatedAt */
                $updatedAt = $user->updated_at ?? null;
                app('misc-debugbar')->debug('User last modified: ' . $updatedAt);
                $options = ['last_modified' => $updatedAt];
                if (!CacheableMiddleware::check($options)) {
                    return;
                }

                app('misc-debugbar')->debug('Using user last modified');
                $response->setCache($options);
            });

            CacheableMiddleware::checking(function (Response $response) {
                CacheableMiddleware::cacheView($response);
            });
        }
    }
}
