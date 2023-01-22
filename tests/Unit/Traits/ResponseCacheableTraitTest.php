<?php

namespace KieranFYI\Tests\Misc\Unit\Traits;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use KieranFYI\Misc\Traits\ResponseCacheable;
use KieranFYI\Tests\Misc\TestCase;
use Throwable;

class ResponseCacheableTraitTest extends TestCase
{
    use ResponseCacheable;

    /**
     * Setup the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['HTTP_If-Modified-Since']);
    }

    public function testCachedDisabled()
    {
        Config::set('misc.cache.enabled', false);
        $this->assertFalse($this->cached());
    }

    /**
     * @throws Throwable
     */
    public function testCached()
    {
        Config::set('misc.cache.enabled', true);
        $this->assertFalse($this->cached());
        $this->assertNull(CacheableMiddleware::$timestamp);
    }

    /**
     * @throws Throwable
     */
    public function testCachedWithTimestamp()
    {
        Config::set('misc.cache.enabled', true);
        $timestamp = Carbon::now();

        $this->assertFalse($this->cached($timestamp));
        $this->assertNotNull(CacheableMiddleware::$timestamp);
        $this->assertTrue($timestamp->equalTo(CacheableMiddleware::$timestamp));
    }

    /**
     * @throws Throwable
     */
    public function testCachedWithTimestampAndRequestIsNotModified()
    {
        $timestamp = Carbon::now();
        $_SERVER['HTTP_If-Modified-Since'] = $timestamp->toString();
        Config::set('misc.cache.enabled', true);

        $this->expectException(HttpException::class);
        $this->cached($timestamp);
    }

    /**
     * @throws Throwable
     */
    public function testCachedWithTimestampAndRequestIsNotModifiedNoThrow()
    {
        $timestamp = Carbon::now();
        $_SERVER['HTTP_If-Modified-Since'] = $timestamp->toString();
        Config::set('misc.cache.enabled', true);

        $this->assertTrue($this->cached($timestamp, false));
        $this->assertNotNull(CacheableMiddleware::$timestamp);
        $this->assertTrue($timestamp->equalTo(CacheableMiddleware::$timestamp));
    }
}