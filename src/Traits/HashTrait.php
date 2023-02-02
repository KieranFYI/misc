<?php

namespace KieranFYI\Misc\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HashTrait
{
    /**
     * @return void
     */
    public function initializeHashTrait(): void
    {
        $this->fillable[] = 'hash';
    }

    /**
     * @return void
     */
    protected static function bootHashTrait(): void
    {
        static::creating(function (Model $model) {
            $length = 5;
            for ($i = 0; $i < 20; $i++) {
                $hash = bin2hex(random_bytes($length + $i));

                if (self::where('hash', $hash)->exists()) {
                    // @codeCoverageIgnoreStart
                    continue;
                    // @codeCoverageIgnoreEnd
                }

                /** @var static $model */
                $model->setAttribute('hash', $hash);
                return;
            }

            // @codeCoverageIgnoreStart
            throw new Exception('Unable to generate unique Hash');
            // @codeCoverageIgnoreEnd
        });
    }

    /**
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'hash';
    }
}
