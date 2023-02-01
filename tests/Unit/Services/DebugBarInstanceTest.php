<?php

namespace KieranFYI\Tests\Misc\Unit\Services;

use Barryvdh\Debugbar\ServiceProvider;
use Closure;
use DebugBar\DataCollector\MessagesCollector;
use Illuminate\Foundation\Application;
use KieranFYI\Misc\Services\DebugBar;
use KieranFYI\Tests\Misc\TestCase;
use Mockery;

class DebugBarInstanceTest extends TestCase
{
    /**
     * @var DebugBar
     */
    private DebugBar $debugBar;

    /**
     * Load package service provider.
     *
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [
            ServiceProvider::class
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->debugBar = new DebugBar();
    }

    public function testInstance()
    {
        $class = 'Barryvdh\Debugbar\LaravelDebugbar';
        $instance = $this->app->get($class);
        $this->assertInstanceOf($class, $instance);
        $this->assertEquals($instance, $this->debugBar->instance());
    }

    public function testMeasure()
    {
        $this->debugBar->measure('testing', function () {
            $this->assertTrue(true);
        });
    }

    public function testCallFunction()
    {
        $this->debugBar->__call('measure', ['testing', function () {
            $this->assertTrue(true);
        }]);
    }

    public function testCallMagic()
    {
        $this->debugBar->__call('info', ['Test message']);
        $this->assertTrue(true);
    }
}