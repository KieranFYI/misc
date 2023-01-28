<?php

namespace KieranFYI\Misc\Services;

use Closure;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * @method Barryvdh\Debugbar\LaravelDebugbar addCollector(\DebugBar\DataCollector\DataCollectorInterface $collector)
 * @method void addMessage(mixed $message, string $label = 'info')
 * @method void alert(mixed $message)
 * @method void critical(mixed $message)
 * @method void debug(mixed $message)
 * @method void emergency(mixed $message)
 * @method void error(mixed $message)
 * @method Barryvdh\Debugbar\LaravelDebugbar getCollector(string $name)
 * @method bool hasCollector(string $name)
 * @method void info(mixed $message)
 * @method void log(mixed $message)
 * @method void notice(mixed $message)
 * @method void warning(mixed $message)
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
     * @throws BindingResolutionException
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

        if (method_exists($instance, $name)) {
            return call_user_func([$instance, $name], ...$arguments);
        }
        return $instance->__call($name, $arguments);
    }

}