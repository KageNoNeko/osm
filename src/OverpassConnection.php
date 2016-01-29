<?php

namespace KageNoNeko\OSM;

use Closure;
use Exception;
use Illuminate\Support\Arr;
//use Illuminate\Database\Query\Processors\Processor;
use GuzzleHttp\Client;
use KageNoNeko\OSM\Query\OverpassBuilder as QueryBuilder;
use KageNoNeko\OSM\Query\Grammars\OverpassGrammar as QueryGrammar;
use KageNoNeko\OSM\Query\Exception as QueryException;

class OverpassConnection implements ConnectionInterface
{

    /**
     * The active client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The query grammar implementation.
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    protected $queryGrammar;

    /**
     * The query post processor implementation.
     *
     * @var \Illuminate\Database\Query\Processors\Processor
     */
    //protected $postProcessor;

    /**
     * All of the queries run against the connection.
     *
     * @var array
     */
    protected $queryLog = [];

    /**
     * Indicates whether queries are being logged.
     *
     * @var bool
     */
    protected $loggingQueries = false;

    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Create a new osm connection instance.
     *
     * @param  array $config
     */
    public function __construct(array $config = []) {

        $this->config = $config;

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
        $this->useDefaultQueryGrammar();

        //$this->useDefaultPostProcessor();

        $this->useDefaultClient();
    }

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultQueryGrammar() {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar() {
        return new QueryGrammar;
    }

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultClient() {
        $this->client = $this->getDefaultClient();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultClient() {
        return new Client(['base_uri' => $this->getConfig('interpreter')]);
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    /*public function useDefaultPostProcessor() {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }*/

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    /*protected function getDefaultPostProcessor() {
        return new Processor;
    }*/

    /**
     * Begin a fluent query against a osm element.
     *
     * @param  string $element
     *
     * @return \KageNoNeko\OSM\Query\OverpassBuilder
     */
    public function element($element) {
        return $this->query()->element($element);
    }

    /**
     * Get a new query builder instance.
     *
     * @return \KageNoNeko\OSM\Query\OverpassBuilder
     */
    public function query() {
        return new QueryBuilder(
            $this, $this->getQueryGrammar()//, $this->getPostProcessor()
        );
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string $query
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function runQuery($query) {
        return $this->run($query, function ($me, $query) {

            return $this->getClient()->post(null, ['data' => $query]);
        });
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  string   $query
     * @param  \Closure $callback
     *
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function run($query, Closure $callback) {

        $start = microtime(true);
        $result = $this->runQueryCallback($query, $callback);
        $time = $this->getElapsedTime($start);

        $this->logQuery($query, $time);

        return $result;
    }

    protected function runQueryCallback($query, Closure $callback) {
        try {
            $result = $callback($this, $query);
        } catch (Exception $e) {
            throw new QueryException(
                $query, $e
            );
        }

        return $result;
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string     $query
     * @param  float|null $time
     *
     * @return void
     */
    public function logQuery($query, $time = null) {
        if (!$this->loggingQueries) {
            return;
        }

        $this->queryLog[] = compact('query', 'time');
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int $start
     *
     * @return float
     */
    protected function getElapsedTime($start) {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Get the current client.
     *
     * @return \GuzzleHttp\Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * Set the client.
     *
     * @param  \GuzzleHttp\Client $client
     *
     * @return $this
     */
    public function setClient(Client $client) {

        $this->client = $client;

        return $this;
    }

    /**
     * Get an option from the configuration options.
     *
     * @param  string $option
     *
     * @return mixed
     */
    public function getConfig($option) {
        return Arr::get($this->config, $option);
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return \KageNoNeko\OSM\Query\Grammars\OverpassGrammar
     */
    public function getQueryGrammar() {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param  \KageNoNeko\OSM\Query\Grammars\OverpassGrammar $grammar
     *
     * @return void
     */
    public function setQueryGrammar(QueryGrammar $grammar) {
        $this->queryGrammar = $grammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    /*public function getPostProcessor() {
        return $this->postProcessor;
    }*/

    /**
     * Set the query post processor used by the connection.
     *
     * @param  \Illuminate\Database\Query\Processors\Processor $processor
     *
     * @return void
     */
    /*public function setPostProcessor(Processor $processor) {
        $this->postProcessor = $processor;
    }*/

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog() {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog() {
        $this->queryLog = [];
    }

    /**
     * Enable the query log on the connection.
     *
     * @return void
     */
    public function enableQueryLog() {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog() {
        $this->loggingQueries = false;
    }

    /**
     * Determine whether we're logging queries.
     *
     * @return bool
     */
    public function logging() {
        return $this->loggingQueries;
    }
}
