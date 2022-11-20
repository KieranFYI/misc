<?php

namespace KieranFYI\Misc\Traits;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Misc\Exceptions\ImmutableModelException;

/**
 * @mixin Model
 */
trait ImmutableTrait
{

    /**
     * @var array
     */
    private array $whitelist = ['deleted_at'];

    /**
     * @param $key
     * @param $value
     * @throws ImmutableModelException
     */
    public function __set($key, $value)
    {
        if ($this->exists && !in_array($key, $this->whitelist)) {
            throw new ImmutableModelException();
        }

        $this->setAttribute($key, $value);
    }
}
