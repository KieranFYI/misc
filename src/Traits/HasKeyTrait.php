<?php

namespace KieranFYI\Misc\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $key
 * 
 * @mixin Model
 */
trait HasKeyTrait
{
    /**
     * @return void
     */
    public function initializeHasKeyTrait(): void
    {
        array_push($this->fillable, 'key');
    }

    /**
     * @return void
     */
    protected static function bootHasKeyTrait(): void
    {
        static::creating(function (Model $model) {
            $model->setAttribute('key', (string)Str::uuid());
        });
    }
    
    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'key';
    }
}