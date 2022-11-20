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
     * @return string
     * @throws Exception
     */
    public static function generateHash(): string
    {
        for ($i = 0; $i < 20; $i++) {
            $hash = bin2hex(random_bytes(5));
            if (self::where('hash', $hash)->exists()) {
                continue;
            }

            return $hash;
        }

        throw new Exception('Unable to generate unique Hash');
    }

    /**
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'hash';
    }
}
