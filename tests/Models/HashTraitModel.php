<?php

namespace KieranFYI\Tests\Misc\Models;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\HashTrait;

class HashTraitModel extends Model
{
    use HashTrait;
}