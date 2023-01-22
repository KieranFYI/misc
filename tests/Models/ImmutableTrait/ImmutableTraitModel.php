<?php

namespace KieranFYI\Tests\Misc\Models\ImmutableTrait;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\ImmutableTrait;

class ImmutableTraitModel extends Model
{
    use ImmutableTrait;

    /**
     * @var string
     */
    protected $table = 'immutable_trait_models';
}