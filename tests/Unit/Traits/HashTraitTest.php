<?php

namespace KieranFYI\Tests\Misc\Unit\Traits;

use Exception;
use Illuminate\Support\Facades\Schema;
use KieranFYI\Tests\Misc\Models\HashTrait\HashTraitCustomModel;
use KieranFYI\Tests\Misc\Models\HashTrait\HashTraitModel;
use KieranFYI\Tests\Misc\Models\HashTrait\HashTraitInvalidModel;
use KieranFYI\Tests\Misc\TestCase;
use TypeError;

class HashTraitTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('hash_trait_models', function ($table) {
            $table->temporary();
            $table->increments('id');
            $table->string('hash');
            $table->timestamps();
        });
    }

    public function testFillable()
    {
        $model = new HashTraitModel();
        $this->assertContains('hash', $model->getFillable());
    }

    /**
     * @throws Exception
     * @depends testFillable
     */
    public function testGenerateHash()
    {
        $model = new HashTraitModel();
        $model->save();
        $this->assertIsString($model->hash);
    }

    /**
     * @depends testGenerateHash
     */
    public function testGenerateHashCollision()
    {
        $this->markTestSkipped('Unable to reliably test collisions');
    }

    public function testHashColumn()
    {
        $model = new HashTraitModel();
        $this->assertEquals('hash', $model->hashColumn());
    }

    public function testHashColumnInvalid()
    {
        $this->expectException(TypeError::class);
        new HashTraitInvalidModel();
    }

    public function testHashColumnCustom()
    {
        $model = new HashTraitCustomModel();
        $this->assertEquals('test', $model->hashColumn());
    }

    public function testGetRouteKeyName()
    {
        $model = new HashTraitModel();
        $this->assertIsString($model->getRouteKeyName());
        $this->assertEquals('hash', $model->getRouteKeyName());
    }
}
