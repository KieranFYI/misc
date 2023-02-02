<?php

namespace KieranFYI\Tests\Misc\Unit\Middleware;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use KieranFYI\Misc\Facades\Cacheable;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use KieranFYI\Misc\Traits\ResponseCacheable;
use KieranFYI\Tests\Misc\Http\Controllers\TestController;
use KieranFYI\Tests\Misc\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CacheableMiddlewareTest extends TestCase
{
    use ResponseCacheable;

    private CacheableMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CacheableMiddleware();
        Config::set('misc.cache.enabled', false);
        Cacheable::timestamp(null);
    }

    /**
     * Setup the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['HTTP_If-Modified-Since']);
    }

    public function testHandle()
    {
        $response = $this->middleware->handle(Request::createFromGlobals(), function () {
            return response()->json();
        });
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testHandleCacheEnabled()
    {
        $timestamp = Carbon::now()
            ->addWeek();
        Config::set('misc.cache.enabled', true);
        $response = $this->middleware->handle(Request::createFromGlobals(), function (Request $request) use ($timestamp) {
            $this->cached($timestamp);
            return response()->json();
        });
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertNotNull(Cacheable::timestamp());
    }

    public function testHandleCacheEnabledHttpException()
    {
        $this->expectException(Exception::class);
        Config::set('misc.cache.enabled', true);
        $this->middleware->handle(Request::createFromGlobals(), function (Request $request) {
            abort(200);
        });
    }

    public function testHandleCacheEnabledCustom()
    {
        $lastModified = Carbon::now()
            ->addWeek()
            ->micro(0);
        Cacheable::timestamp($lastModified);
        $_SERVER['HTTP_If-Modified-Since'] = $lastModified->toString();
        Config::set('misc.cache.enabled', true);
        $response = $this->middleware->handle(Request::createFromGlobals(), function (Request $request) {
            abort(304);
        });
        $this->assertTrue($lastModified->equalTo($response->getLastModified()));
    }

    public function testHandleCached()
    {
        $lastModified = Cacheable::cacheView()->micro(0);
        $_SERVER['HTTP_If-Modified-Since'] = $lastModified->toString();
        Config::set('misc.cache.enabled', true);
        Route::get('cached', [TestController::class, 'testDefault'])
            ->middleware('cacheable');
        $response = $this->withHeaders([
            'If-Modified-Since' => $lastModified->toString(),
        ])
            ->call('GET', 'cached');
        $this->assertEquals(304, $response->status());
    }

    public function testHandleCachedDisabled()
    {
        $lastModified = Cacheable::cacheView()->micro(0);
        $_SERVER['HTTP_If-Modified-Since'] = $lastModified->toString();
        Route::get('cached', [TestController::class, 'testDefault'])
            ->middleware('cacheable');
        $response = $this->withHeaders([
            'If-Modified-Since' => $lastModified->toString(),
        ])
            ->call('GET', 'cached');
        $this->assertEquals(200, $response->status());
    }

//    public function testHandleCacheEnabledWithTimestamp()
//    {
//        $timestamp = Carbon::now()->micro(0);
//        $_SERVER['HTTP_If-Modified-Since'] = $timestamp->toString();
//        Config::set('misc.cache.enabled', true);
//        $response = $this->middleware->handle(Request::createFromGlobals(), function () use ($timestamp) {
//            $this->cached($timestamp);
//            return response()->json();
//        });
//        $this->assertInstanceOf(Response::class, $response);
//        $this->assertTrue($timestamp->equalTo($response->getLastModified()));
//    }

}