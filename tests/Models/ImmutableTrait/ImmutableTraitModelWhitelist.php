<?php

namespace KieranFYI\Tests\Misc\Models\ImmutableTrait;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\ImmutableTrait;

class ImmutableTraitModelWhitelist extends ImmutableTraitModel
{
    /**
     * @var array
     */
    public array $whitelist = [
        'data'
    ];
}