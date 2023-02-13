<?php

namespace KieranFYI\Tests\Misc\Models\HashTrait;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\HashTrait;

class HashTraitCustomModel extends Model
{
    use HashTrait;

    /**
     * @var string
     */
    public string $hash_column = 'test';
}