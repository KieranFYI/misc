<?php

namespace KieranFYI\Tests\Misc\Unit\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use KieranFYI\Misc\Traits\ResponseCacheable;
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
    }

    /**
     * Setup the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['HTTP_If-Modified-Since']);
        CacheableMiddleware::$timestamp = null;
    }

    public function testCallables()
    {
        $this->assertEmpty(CacheableMiddleware::callables());
        CacheableMiddleware::checking(function () {
            $this->assertTrue(true);
            return null;
        });

        foreach (CacheableMiddleware::callables() as $callable) {
            $callable();
        }

        $this->assertEquals(2, $this->getCount());
    }

    public function testCheck()
    {
        $options = ['last_modified' => Carbon::now()];
        $this->assertFalse(CacheableMiddleware::check($options));
    }

    public function testCheckWithIfModifiedSince()
    {
        $timestamp = Carbon::now();
        $_SERVER['HTTP_If-Modified-Since'] = $timestamp->toString();
        $options = ['last_modified' => $timestamp];
        $this->assertTrue(CacheableMiddleware::check($options));
    }

    public function testHandle()
    {
        Config::set('misc.cache.enabled', false);
        $response = $this->middleware->handle(Request::createFromGlobals(), function () {
            return response()->json();
        });
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testHandleCacheEnabled()
    {
        $timestamp = Carbon::now();
        Config::set('misc.cache.enabled', true);
        $response = $this->middleware->handle(Request::createFromGlobals(), function (Request $request) use ($timestamp) {
            $this->cached($timestamp);
            return response()->json();
        });
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertNotNull(CacheableMiddleware::$timestamp);
    }

    public function testHandleCacheEnabledWithTimestamp()
    {
        $timestamp = Carbon::now()->micro(0);
        $_SERVER['HTTP_If-Modified-Since'] = $timestamp->toString();
        Config::set('misc.cache.enabled', true);
        $response = $this->middleware->handle(Request::createFromGlobals(), function () use ($timestamp) {
            $this->cached($timestamp);
            return response()->json();
        });
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($timestamp->equalTo($response->getLastModified()));
    }

    public function testCacheView()
    {
        $timestamp = CacheableMiddleware::cacheView();
        $this->assertNotNull($timestamp);
    }

    public function testParams()
    {
        $timestamp = CacheableMiddleware::params();
        $this->assertNull($timestamp);
    }
}