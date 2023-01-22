<?php

namespace KieranFYI\Tests\Misc\Unit\Traits;

use Illuminate\Support\Facades\Schema;
use KieranFYI\Misc\Exceptions\ImmutableModelException;
use KieranFYI\Tests\Misc\Models\ImmutableTrait\ImmutableTraitModel;
use KieranFYI\Tests\Misc\Models\ImmutableTrait\ImmutableTraitModelInvalidWhitelist;
use KieranFYI\Tests\Misc\Models\ImmutableTrait\ImmutableTraitModelWhitelist;
use KieranFYI\Tests\Misc\TestCase;
use TypeError;

class ImmutableTraitTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('immutable_trait_models', function ($table) {
            $table->temporary();
            $table->increments('id');
            $table->string('data')
                ->nullable();
            $table->timestamps();
        });
    }

    public function testWhitelist()
    {
        $model = new ImmutableTraitModel();
        $this->assertIsArray($model->whitelist());
    }

    public function testWhitelistWithBadCustomWhitelist()
    {
        $model = new ImmutableTraitModelInvalidWhitelist();
        $this->expectException(TypeError::class);

        $model->whitelist();
    }

    public function testWhitelistBeforeSave()
    {
        $model = new ImmutableTraitModel();
        $model->data = 'test';
        $this->assertTrue(true);
    }

    public function testWhitelistAfterSave()
    {
        $model = new ImmutableTraitModel();
        $model->save();
        $this->expectException(ImmutableModelException::class);

        $model->data = 'test';
    }

    public function testWhitelistWithCustomWhitelist()
    {
        $model = new ImmutableTraitModelWhitelist();
        $model->save();

        $model->data = 'test';
        $this->assertTrue(true);
    }

}