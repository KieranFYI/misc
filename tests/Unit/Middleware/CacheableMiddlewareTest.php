<?php

namespace KieranFYI\Tests\Misc\Unit\Middleware;

use Exception;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use KieranFYI\Misc\Traits\ResponseCacheable;
use KieranFYI\Tests\Misc\Http\Controllers\TestController;
use KieranFYI\Tests\Misc\Models\TestModel;
use KieranFYI\Tests\Misc\Policies\TestModelPolicy;
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
        CacheableMiddleware::checking(function () {
            $this->assertTrue(true);
            return null;
        });

        foreach (CacheableMiddleware::callables() as $callable) {
            $callable();
        }

        $this->assertEquals(1, $this->getCount());
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
        CacheableMiddleware::$timestamp = $lastModified;
        $_SERVER['HTTP_If-Modified-Since'] = $lastModified->toString();
        Config::set('misc.cache.enabled', true);
        $response = $this->middleware->handle(Request::createFromGlobals(), function (Request $request) {
            abort(304);
        });
        $this->assertTrue($lastModified->equalTo($response->getLastModified()));
    }

    public function testHandleCached()
    {
        $lastModified = CacheableMiddleware::cacheView()->micro(0);
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
        $lastModified = CacheableMiddleware::cacheView()->micro(0);
        $_SERVER['HTTP_If-Modified-Since'] = $lastModified->toString();
        Route::get('cached', [TestController::class, 'testDefault'])
            ->middleware('cacheable');
        $response = $this->withHeaders([
            'If-Modified-Since' => $lastModified->toString(),
        ])
            ->call('GET', 'cached');
        $this->assertEquals(200, $response->status());
    }

    public function testHandleCachedReturn()
    {
        Config::set('misc.cache.enabled', true);
        $lastModified = CacheableMiddleware::cacheView()->micro(0);
        $_SERVER['HTTP_If-Modified-Since'] = $lastModified->toString();
        $this->assertTrue($this->cached($lastModified, throw: false));
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

    public function testCacheView()
    {
        $timestamp = CacheableMiddleware::cacheView();
        $this->assertNotNull($timestamp);
    }

    public function testCacheViewDefault()
    {
        $lastModified = CacheableMiddleware::cacheView();
        Config::set('misc.cache.enabled', true);
        Route::get('default', [TestController::class, 'testDefault'])
            ->middleware('cacheable');
        $response = $this->call('GET', 'default');
        $this->assertTrue($lastModified->equalTo($response->getLastModified()));
    }

    public function testUser()
    {
        Schema::create('users', function ($table) {
            $table->temporary();
            $table->id();
            $table->timestamps();
        });
        $user = new User();
        $user->save();
        $this->actingAs($user);

        $timestamp = CacheableMiddleware::user();
        $this->assertEquals($user->updated_at, $timestamp);
    }

    public function testParams()
    {
        $timestamp = CacheableMiddleware::params();
        $this->assertNull($timestamp);
    }

    public function testParamsSignature()
    {
        Config::set('misc.cache.enabled', true);
        Schema::create('test_models', function ($table) {
            $table->temporary();
            $table->increments('id');
            $table->timestamps();
        });
        $updatedAt = Carbon::now()
            ->addWeek()
            ->micro(0);
        Route::get('signature/{model}', [TestController::class, 'testSignature'])
            ->middleware('cacheable');
        $model = TestModel::create([
            'updated_at' => $updatedAt
        ]);
        $response = $this->call('GET', 'signature/' . $model->id);
        $this->assertTrue($updatedAt->equalTo($response->getLastModified()));
    }

    public function testParamsMiddleware()
    {
        Config::set('misc.cache.enabled', true);
        Schema::create('test_models', function ($table) {
            $table->temporary();
            $table->increments('id');
            $table->timestamps();
        });
        $updatedAt = Carbon::now()
            ->addWeek()
            ->micro(0);
        Route::get('middleware', [TestController::class, 'testMiddleware'])
            ->middleware('cacheable');
        TestModel::create([
            'updated_at' => $updatedAt
        ]);
        $response = $this->withoutMiddleware([Authorize::class])
            ->call('GET', 'middleware');

        $this->assertTrue($updatedAt->equalTo($response->getLastModified()));
    }
}