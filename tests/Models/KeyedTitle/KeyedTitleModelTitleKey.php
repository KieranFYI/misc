<?php

namespace KieranFYI\Tests\Misc\Models\KeyedTitle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use KieranFYI\Misc\Traits\KeyedTitle;

class KeyedTitleModelTitleKey extends KeyedTitleModel
{
    use KeyedTitle;
    use SoftDeletes;

    /**
     * @var string
     */
    public string $title_key = 'name';
}