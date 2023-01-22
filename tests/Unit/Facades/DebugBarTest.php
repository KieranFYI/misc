<?php

namespace KieranFYI\Tests\Misc\Unit\Facades;

use KieranFYI\Misc\Facades\Debugbar;
use KieranFYI\Misc\Services\DebugBar as DebugBarService;
use KieranFYI\Tests\Misc\TestCase;

class DebugBarTest extends TestCase
{
    public function testProvider()
    {
        $this->assertInstanceOf(DebugBarService::class, Debugbar::getFacadeRoot());
    }
}