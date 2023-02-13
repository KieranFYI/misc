<?php

namespace KieranFYI\Tests\Misc\Unit\Traits;

use Illuminate\Support\Facades\Schema;
use KieranFYI\Tests\Misc\Models\HasKeyTrait\HasKeyTraitCustomModel;
use KieranFYI\Tests\Misc\Models\HasKeyTrait\HasKeyTraitInvalidModel;
use KieranFYI\Tests\Misc\Models\HasKeyTrait\HasKeyTraitModel;
use KieranFYI\Tests\Misc\TestCase;
use TypeError;

class HasKeyTraitTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('has_key_trait_models', function ($table) {
            $table->temporary();
            $table->increments('id');
            $table->string('key');
            $table->timestamps();
        });
    }

    public function testFillable()
    {
        $model = new HasKeyTraitModel();
        $this->assertContains('key', $model->getFillable());
    }

    /**
     * @depends testFillable
     */
    public function testGenerateKey()
    {
        $model = HasKeyTraitModel::create();
        $this->assertIsString($model->key);
    }

    public function testKeyColumn()
    {
        $model = new HasKeyTraitModel();
        $this->assertEquals('key', $model->keyColumn());
    }

    public function testKeyColumnInvalid()
    {
        $this->expectException(TypeError::class);
        new HasKeyTraitInvalidModel();
    }

    public function testKeyColumnCustom()
    {
        $model = new HasKeyTraitCustomModel();
        $this->assertEquals('test', $model->keyColumn());
    }

    public function testGetRouteKeyName()
    {
        $model = new HasKeyTraitModel();
        $this->assertIsString($model->getRouteKeyName());
        $this->assertEquals('key', $model->getRouteKeyName());
    }
}
