<?php

namespace KieranFYI\Tests\Misc\Unit\Services;

use Closure;
use KieranFYI\Misc\Services\DebugBar;
use KieranFYI\Tests\Misc\TestCase;
use Mockery;

class DebugBarTest extends TestCase
{

    public function testInstance()
    {
        $debugBar = new DebugBar();
        $this->assertFalse($debugBar->instance());
    }

    public function testInstanceMock()
    {
        $class = 'Barryvdh\Debugbar\LaravelDebugbar';
        $instance = Mockery::mock(new class() {
        });
        app()->singleton($class, function () use ($instance) {
            return $instance;
        });
        $debugBar = new DebugBar();
        $this->assertEquals($instance, $debugBar->instance());
    }

    public function testMeasure()
    {
        $debugBar = new DebugBar();
        $debugBar->measure('testing', function () {
            $this->assertTrue(true);
        });
    }

    public function testMeasureWithInstance()
    {
        $class = 'Barryvdh\Debugbar\LaravelDebugbar';
        app()->singleton($class, function () {
            return Mockery::mock(new class() {
                function measure($label, Closure $closure)
                {
                    return $closure();
                }
            });
        });
        $debugBar = new DebugBar();
        $debugBar->measure('testing', function () {
            $this->assertTrue(true);
        });
    }
}