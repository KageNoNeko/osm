<?php

namespace KageNoNeko\OSM\Illuminate;

use InvalidArgumentException;
use Illuminate\Support\Str;

class ConnectionFactory
{

    /**
     * Create a new connection factory instance.
     */
    public function __construct() {
    }

    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array  $config
     * @param  string $driver
     *
     * @return \Illuminate\Database\Connection
     */
    public function make($driver, array $config = []) {

        if (method_exists($this, $method = "make" . Str::studly($driver) ."Connection")) {
            return $this->$method($config);
        }

        throw new InvalidArgumentException("Unsupported connection [$driver]");
    }

    protected function makeOverpassConnection(array $config = []) {
        return new OverpassConnection($config);
    }
}
