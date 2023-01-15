<?php

namespace KieranFYI\Misc\Services;

use Closure;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * @method static Barryvdh\Debugbar\LaravelDebugbar addCollector(\DebugBar\DataCollector\DataCollectorInterface $collector)
 * @method static void addMessage(mixed $message, string $label = 'info')
 * @method static void alert(mixed $message)
 * @method static void critical(mixed $message)
 * @method static void debug(mixed $message)
 * @method static void emergency(mixed $message)
 * @method static void error(mixed $message)
 * @method static Barryvdh\Debugbar\LaravelDebugbar getCollector(string $name)
 * @method static bool hasCollector(string $name)
 * @method static void info(mixed $message)
 * @method static void log(mixed $message)
 * @method static void notice(mixed $message)
 * @method static void warning(mixed $message)
 *
 * @see \Barryvdh\Debugbar\LaravelDebugbar
 */
class DebugBar extends ServiceProvider
{
    /**
     * @var mixed
     */
    private mixed $instance = null;

    private Application $application;

    /**
     * @param Application|null $application
     */
    public function __construct($application = null)
    {
        if (!$application) {
            $application = app();
        }

        $this->application = $application;
    }

    /**
     * @return mixed
     */
    public function instance(): mixed
    {
        if (is_null($this->instance)) {
            $class = 'Barryvdh\Debugbar\LaravelDebugbar';
            if ($this->application->has($class)) {
                $this->instance = $this->application->make($class);
            }
        }

        return $this->instance ?? false;
    }

    /**
     * @param $label
     * @param Closure $closure
     * @return mixed
     */
    public function measure($label, Closure $closure): mixed
    {
        if ($this->instance() === false || !method_exists($this->instance(), 'measure')) {
            return $closure();
        }

        return $this->instance()->measure($label, $closure);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $instance = $this->instance();
        if ($instance === false) {
            return;
        }

        try {
            if (method_exists($instance, $name)) {
                return call_user_func([$instance, $name], ...$arguments);
            }
            return $instance->__call($name, $arguments);
        } catch (Exception) {
        }
    }

}