<?php

namespace KageNoNeko\OSM;

use Closure;

interface ConnectionInterface
{

    /**
     * Get a new query builder instance.
     *
     * @return \KageNoNeko\OSM\Query\Builder
     */
    public function query();

    /**
     * Begin a fluent query against a osm element.
     *
     * @param  string  $element
     * @return \KageNoNeko\OSM\Query\Builder
     */
    public function element($element);

    /**
     * Run a select statement against the source.
     *
     * @param  string  $query
     * @return array
     */
    public function runQuery($query);
}
