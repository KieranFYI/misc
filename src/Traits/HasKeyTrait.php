<?php

namespace KieranFYI\Misc\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait HasKeyTrait
{
    protected static function bootHasKeyTrait()
    {
        static::creating(function ($model) {
            $model->key = (string)Str::uuid();
        });
    }
}