<?php

namespace KageNoNeko\OSM;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

/**
 * @see \KageNoNeko\OSM\Manager
 * @see \KageNoNeko\OSM\ConnectionInterface
 */
class Facade extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'osm';
    }
}
