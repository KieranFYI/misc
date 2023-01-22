<?php

namespace KieranFYI\Tests\Misc\Models;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\HasKeyTrait;

class HasKeyTraitModel extends Model
{
    use HasKeyTrait;
}