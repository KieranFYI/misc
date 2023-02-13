<?php

namespace KieranFYI\Misc\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use TypeError;

/**
 * @property string $hash_column
 *
 * @mixin Model
 */
trait HashTrait
{
    /**
     * @return void
     */
    protected static function bootHashTrait(): void
    {
        static::creating(function (Model $model) {
            $length = 5;
            for ($i = 0; $i < 20; $i++) {
                $hash = bin2hex(random_bytes($length + $i));

                if (self::where($model->hashColumn(), $hash)->exists()) {
                    // @codeCoverageIgnoreStart
                    continue;
                    // @codeCoverageIgnoreEnd
                }

                /** @var static $model */
                $model->setAttribute($model->hashColumn(), $hash);
                return;
            }

            // @codeCoverageIgnoreStart
            throw new Exception('Unable to generate unique Hash');
            // @codeCoverageIgnoreEnd
        });
    }

    /**
     * @return void
     */
    public function initializeHashTrait(): void
    {
        $this->fillable[] = $this->hashColumn();
    }

    /**
     * @return string
     */
    public function hashColumn(): string
    {
        if (property_exists($this, 'hash_column')) {
            if (!is_string($this->hash_column)) {
                throw new TypeError(self::class . '::hashColumn(): Property ($hash_column) must be of type string');
            }

            return $this->hash_column;
        }
        return 'hash';
    }

    /**
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->hashColumn();
    }
}
