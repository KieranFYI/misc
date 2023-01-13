<?php

namespace KieranFYI\Misc\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Barryvdh\Debugbar\LaravelDebugbar addCollector(\DebugBar\DataCollector\DataCollectorInterface $collector)
 * @method static void startMeasure($name, $label = null)
 * @method static void stopMeasure($name)
 * @method static mixed measure($label, \Closure $closure)
 * @method static void addMeasure($label, $start, $end, $params = array(), $collector = null)
 * @method static void addMessage($message, $label = 'info')
 * @method static void alert(mixed $message)
 * @method static void critical(mixed $message)
 * @method static void debug(mixed $message)
 * @method static void emergency(mixed $message)
 * @method static void error(mixed $message)
 * @method static \Barryvdh\Debugbar\LaravelDebugbar getCollector(string $name)
 * @method static bool hasCollector(string $name)
 * @method static void info(mixed $message)
 * @method static void log(mixed $message)
 * @method static void notice(mixed $message)
 * @method static void warning(mixed $message)
 *
 * @see \Barryvdh\Debugbar\LaravelDebugbar
 */
class Debugbar extends Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'misc-debugbar';
    }
}
