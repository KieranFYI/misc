<?php

namespace KieranFYI\Misc\Facades;

use Carbon\Carbon;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array callables()
 * @method static void checking(callable $callable)
 * @method static bool check(array $options)
 * @method static bool cached(Carbon $value = null, bool $throw = true)
 * @method static null|Carbon user()
 * @method static Carbon cacheView()
 * @method static null|Carbon params()
 * @method static null|Carbon checkSignature()
 * @method static null|Carbon checkMiddleware()
 * @method static null|Carbon timestamp(Carbon $timestamp = null)
 *
 * @see \KieranFYI\Misc\Services\Cacheable
 */
class Cacheable extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return \KieranFYI\Misc\Services\Cacheable::class;
    }
}