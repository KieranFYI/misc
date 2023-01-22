<?php

namespace KieranFYI\Tests\Misc\Unit\Traits;

use Illuminate\Support\Facades\Schema;
use KieranFYI\Tests\Misc\Models\KeyedTitle\KeyedTitleModel;
use KieranFYI\Tests\Misc\Models\KeyedTitle\KeyedTitleModelInvalidTitleKey;
use KieranFYI\Tests\Misc\Models\KeyedTitle\KeyedTitleModelTitleKey;
use KieranFYI\Tests\Misc\TestCase;
use TypeError;

class KeyedTitleTraitTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('keyed_title_models', function ($table) {
            $table->temporary();
            $table->increments('id');
            $table->string('name')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testGetTitleAttribute()
    {
        $model = new KeyedTitleModel();
        $model->save();
        $this->assertIsString($model->getTitleAttribute());
        $this->assertIsString($model->title);

        $this->assertNotEquals('Unknown', $model->getTitleAttribute());
    }

    public function testGetTitleAttributeUnsaved()
    {
        $model = new KeyedTitleModel();
        $this->assertEquals('Unknown', $model->getTitleAttribute());
    }

    public function testGetTitleAttributeWithTitleKey()
    {
        $model = new KeyedTitleModelTitleKey([
            'name' => 'Testing 123'
        ]);
        $model->save();
        $this->assertEquals('Testing 123', $model->title);
    }

    public function testGetTitleAttributeInvalidTitleKey()
    {
        $model = new KeyedTitleModelInvalidTitleKey();
        $model->save();
        $this->expectException(TypeError::class);

        $model->getTitleAttribute();
    }

    public function testGetTitleDetailedAttribute()
    {
        $model = new KeyedTitleModel();
        $model->save();
        $this->assertIsString($model->getTitleDetailedAttribute());
        $this->assertIsString($model->title_detailed);
    }

    public function testGetTitleDetailedAttributeDeleted()
    {
        $model = new KeyedTitleModel();
        $model->save();
        $model->delete();
        $this->assertStringEndsWith('(Soft Deleted)', $model->getTitleDetailedAttribute());
    }
}