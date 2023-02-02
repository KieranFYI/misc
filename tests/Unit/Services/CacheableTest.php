<?php

namespace KieranFYI\Tests\Misc\Unit\Services;

use Exception;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use KieranFYI\Misc\Facades\Cacheable;
use KieranFYI\Tests\Misc\Http\Controllers\TestController;
use KieranFYI\Tests\Misc\Models\TestModel;
use KieranFYI\Tests\Misc\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CacheableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('misc.cache.enabled', false);
        Cacheable::timestamp(null);
        unset($_SERVER['HTTP_If-Modified-Since']);
    }

    public function testCallables()
    {
        Cacheable::checking(function () {
            $this->assertTrue(true);
            return null;
        });

        foreach (Cacheable::callables() as $callable) {
            $callable();
        }

        $this->assertEquals(1, $this->getCount());
    }

    public function testCheck()
    {
        $options = ['last_modified' => Carbon::now()];
        $this->assertFalse(Cacheable::check($options));
    }

    public function testCheckWithIfModifiedSince()
    {
        $timestamp = Carbon::now();
        $_SERVER['HTTP_If-Modified-Since'] = $timestamp->toString();
        $options = ['last_modified' => $timestamp];
        $this->assertTrue(Cacheable::check($options));
    }

    public function testCached()
    {
        $timestamp = Carbon::now();
        Config::set('misc.cache.enabled', true);
        $this->assertFalse(Cacheable::cached($timestamp, false));
        $this->assertNotNull(Cacheable::timestamp());
    }

    public function testCacheView()
    {
        $timestamp = Cacheable::cacheView();
        $this->assertNotNull($timestamp);
    }

    public function testCacheViewDefault()
    {
        $lastModified = Cacheable::cacheView();
        Config::set('misc.cache.enabled', true);
        Route::get('default', [TestController::class, 'testDefault'])
            ->middleware('cacheable');
        $response = $this->call('GET', 'default');
        $this->assertEquals($lastModified, $response->getLastModified());
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

        $timestamp = Cacheable::user();
        $this->assertEquals($user->updated_at, $timestamp);
    }

    public function testParams()
    {
        $timestamp = Cacheable::params();
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