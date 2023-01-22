<?php

namespace KieranFYI\Tests\Misc\Models\KeyedTitle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use KieranFYI\Misc\Traits\KeyedTitle;

class KeyedTitleModelInvalidTitleKey extends KeyedTitleModel
{
    use KeyedTitle;
    use SoftDeletes;

    /**
     * @var array
     */
    public array $title_key = [];
}