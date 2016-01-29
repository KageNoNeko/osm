<?php

namespace KageNoNeko\OSM\Query;

use RuntimeException;

class Exception extends RuntimeException
{
    /**
     * The query.
     *
     * @var string
     */
    protected $query;

    /**
     * Create a new query exception instance.
     *
     * @param  string     $query
     * @param  \Exception $previous
     */
    public function __construct($query, $previous) {
        parent::__construct('', 0, $previous);

        $this->query = $query;
        $this->previous = $previous;
        $this->code = $previous->getCode();
        $this->message = $this->formatMessage($query, $previous);
    }

    /**
     * Format the query error message.
     *
     * @param  string     $query
     * @param  \Exception $previous
     *
     * @return string
     */
    protected function formatMessage($query, $previous) {
        return "{$previous->getMessage()} (Query: {$query})";
    }

    /**
     * Get the query.
     *
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }
}
