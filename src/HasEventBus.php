<?php

namespace Mckue\Excel;

trait HasEventBus
{
    /**
     * @var array
     */
    protected static array $globalEvents = [];

    /**
     * @var array
     */
    protected array $events = [];

    /**
     * Register local event listeners.
     *
     * @param  array  $listeners
     */
    public function registerListeners(array $listeners): void
    {
        foreach ($listeners as $event => $listener) {
            $this->events[$event][] = $listener;
        }
    }

    public function clearListeners(): void
    {
        $this->events = [];
    }

    /**
     * Register a global event listener.
     *
     * @param  string  $event
     * @param  callable  $listener
     */
    public static function listen(string $event, callable $listener): void
    {
        static::$globalEvents[$event][] = $listener;
    }

    /**
     * @param  object  $event
     */
    public function raise($event): void
    {
        foreach ($this->listeners($event) as $listener) {
            $listener($event);
        }
    }

    /**
     * @param  object  $event
     * @return callable[]
     */
    public function listeners(object $event): array
    {
        $name = \get_class($event);

        $localListeners  = $this->events[$name] ?? [];
        $globalListeners = static::$globalEvents[$name] ?? [];

        return array_merge($globalListeners, $localListeners);
    }
}
