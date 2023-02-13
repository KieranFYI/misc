<?php

namespace KieranFYI\Tests\Misc\Models\HashTrait;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\HashTrait;

class HashTraitInvalidModel extends Model
{
    use HashTrait;

    /**
     * @var array
     */
    public array $hash_column = [];
}