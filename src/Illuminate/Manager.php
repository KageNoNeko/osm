<?php

namespace KageNoNeko\OSM\Illuminate;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use KageNoNeko\OSM\ConnectionFactory;
use KageNoNeko\OSM\ConnectionInterface;

class Manager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The database connection factory instance.
     *
     * @var \KageNoNeko\OSM\ConnectionFactory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The custom connection resolvers.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Create a new database manager instance.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @param  \KageNoNeko\OSM\ConnectionFactory  $factory
     */
    public function __construct($app, ConnectionFactory $factory) {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Get a osm connection instance.
     *
     * @param  string $name
     *
     * @return \KageNoNeko\OSM\ConnectionInterface
     */
    public function connection($name = null) {
        $name = $name ?: $this->getDefaultConnection();

        if (!isset($this->connections[$name])) {
            $connection = $this->makeConnection($name);

            $this->connections[$name] = $this->prepare($connection);
        }

        return $this->connections[$name];
    }

    /**
     * Make the database connection instance.
     *
     * @param  string $name
     *
     * @return \KageNoNeko\OSM\ConnectionInterface
     */
    protected function makeConnection($name) {
        $config = $this->getConfig($name);

        $driver = $config['driver'];

        if (isset($this->extensions[$driver])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($config, $name);
    }

    /**
     * Prepare the database connection instance.
     *
     * @param  \KageNoNeko\OSM\ConnectionInterface $connection
     *
     * @return \KageNoNeko\OSM\ConnectionInterface
     */
    protected function prepare(ConnectionInterface $connection) {

        if ($this->app->bound('events')) {
            $connection->setEventDispatcher($this->app['events']);
        }

        return $connection;
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string $name
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getConfig($name) {
        $name = $name ?: $this->getDefaultConnection();

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->app['config']['osm.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Database [$name] not configured.");
        }

        return $config;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection() {
        return $this->app['config']['osm.default'];
    }

    /**
     * Set the default connection name.
     *
     * @param  string $name
     *
     * @return void
     */
    public function setDefaultConnection($name) {
        $this->app['config']['osm.default'] = $name;
    }

    /**
     * Register an extension connection resolver.
     *
     * @param  string   $name
     * @param  callable $resolver
     *
     * @return void
     */
    public function extend($name, callable $resolver) {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getConnections() {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters) {
        return call_user_func_array([$this->connection(), $method], $parameters);
    }
}
