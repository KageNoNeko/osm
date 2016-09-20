<?php

namespace KageNoNeko\OSM\Illuminate;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Events\Dispatcher;
use KageNoNeko\OSM\OverpassConnection as OSMOverpassConnection;

class OverpassConnection extends OSMOverpassConnection
{

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Log a query in the connection's query log.
     *
     * @param  string     $query
     * @param  float|null $time
     *
     * @return void
     */
    public function logQuery($query, $time = null) {
        if (isset($this->events)) {
            $this->events->fire('osm.query', [$query, $time]);
        }

        parent::logQuery($query, $time);
    }

    /**
     * Get an option from the configuration options using "dot" notation.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function getConfig($key, $default = null) {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Register a database query listener with the connection.
     *
     * @param  \Closure $callback
     *
     * @return void
     */
    public function listen(Closure $callback) {
        if (isset($this->events)) {
            $this->events->listen('osm.query', $callback);
        }
    }

    /**
     * Get the event dispatcher used by the connection.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher() {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher $events
     *
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events) {
        $this->events = $events;
    }
}
