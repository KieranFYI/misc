<?php

namespace KieranFYI\Misc\Traits;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Exceptions\ImmutableModelException;
use TypeError;

/**
 * @property array $whitelist
 *
 * @mixin Model
 */
trait ImmutableTrait
{
    /**
     * Get the policies defined on the provider.
     *
     * @return array<class-string, class-string>
     */
    public function whitelist(): array
    {
        if (property_exists($this, 'whitelist')) {
            if (!is_array($this->whitelist)) {
                throw new TypeError(self::class.'::whitelist(): Property ($whitelist) must be of type array');
            }

            return $this->whitelist;
        }
        return ['deleted_at'];
    }

    /**
     * @param $key
     * @param $value
     * @throws ImmutableModelException
     */
    public function __set($key, $value)
    {
        if ($this->exists && !in_array($key, $this->whitelist()) && is_null($this->getOriginal($key))) {
            throw new ImmutableModelException();
        }

        $this->setAttribute($key, $value);
    }
}
