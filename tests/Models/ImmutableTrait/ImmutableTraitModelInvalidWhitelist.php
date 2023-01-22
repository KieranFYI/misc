<?php

namespace KieranFYI\Tests\Misc\Models\ImmutableTrait;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Traits\ImmutableTrait;

class ImmutableTraitModelInvalidWhitelist extends ImmutableTraitModel
{
    /**
     * @var string
     */
    public string $whitelist = '';
}