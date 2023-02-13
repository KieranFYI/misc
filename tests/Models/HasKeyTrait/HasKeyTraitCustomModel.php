<?php

namespace KieranFYI\Tests\Misc\Models\HasKeyTrait;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\HasKeyTrait;

class HasKeyTraitCustomModel extends Model
{
    use HasKeyTrait;

    /**
     * @var string
     */
    public string $key_column = 'test';

}