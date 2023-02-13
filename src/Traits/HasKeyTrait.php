<?php

namespace KieranFYI\Misc\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use TypeError;

/**
 * @property string $key_column
 *
 * @mixin Model
 */
trait HasKeyTrait
{
    /**
     * @return void
     */
    protected static function bootHasKeyTrait(): void
    {
        static::creating(function (Model $model) {
            $model->setAttribute($model->keyColumn(), (string)Str::uuid());
        });
    }

    /**
     * @return void
     */
    public function initializeHasKeyTrait(): void
    {
        array_push($this->fillable, $this->keyColumn());
    }

    /**     *
     * @return string
     */
    public function keyColumn(): string
    {
        if (property_exists($this, 'key_column')) {
            if (!is_string($this->key_column)) {
                throw new TypeError(self::class . '::hashColumn(): Property ($hash_column) must be of type string');
            }

            return $this->key_column;
        }
        return 'key';
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->keyColumn();
    }
}