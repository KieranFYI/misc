<?php

namespace KieranFYI\Tests\Misc\Models\HasKeyTrait;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\HasKeyTrait;

class HasKeyTraitInvalidModel extends Model
{
    use HasKeyTrait;

    /**
     * @var array
     */
    public array $key_column = [];

}