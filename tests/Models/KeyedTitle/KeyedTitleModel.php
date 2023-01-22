<?php

namespace KieranFYI\Tests\Misc\Models\KeyedTitle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use KieranFYI\Misc\Traits\KeyedTitle;

class KeyedTitleModel extends Model
{
    use KeyedTitle;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'keyed_title_models';

    /**
     * @var string[]
     */
    protected $fillable = ['name'];
}