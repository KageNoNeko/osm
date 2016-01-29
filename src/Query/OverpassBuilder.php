<?php

namespace KageNoNeko\OSM\Query;

use InvalidArgumentException;
use KageNoNeko\OSM\ConnectionInterface;
use KageNoNeko\OSM\Query\Grammars\OverpassGrammar;
//use KageNoNeko\OSM\Query\Processors\Processor;

class OverpassBuilder
{

    /**
     * The database connection instance.
     *
     * @var \KageNoNeko\OSM\ConnectionInterface
     */
    protected $connection;

    /**
     * The database query grammar instance.
     *
     * @var \KageNoNeko\OSM\Query\Grammars\OverpassGrammar
     */
    protected $grammar;

    /**
     * The database query post processor instance.
     *
     * @var \KageNoNeko\OSM\Query\Processors\Processor
     */
    //protected $processor;

    /**
     * The maximum number of records to return.
     *
     * @var array
     */
    protected $settings = [];

    /**
     * The where constraints for the query.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * The orderings for the query.
     *
     * @var array
     */
    protected $out = [
        'limit' => null,
        'order' => null,
        'type' => null,
    ];

    /**
     * All of the available elements to fetch.
     *
     * @var array
     */
    protected $elements = [
        'node', 'way', 'area', 'rel',
    ];

    /**
     * All of the available elements to fetch.
     *
     * @var array
     */
    protected $orders = [
        'asc', 'qt',
    ];

    /**
     * All of the available clause tag operators.
     *
     * @var array
     */
    protected $tagOperators = [
        '=', '!=', '~', '!~', 'exists',
    ];

    protected $lastElement;

    /**
     * Create a new query builder instance.
     *
     * @param  \KageNoNeko\OSM\ConnectionInterface        $connection
     * @param  \KageNoNeko\OSM\Query\Grammars\OverpassGrammar     $grammar
     * @param  \KageNoNeko\OSM\Query\Processors\Processor $processor
     */
    public function __construct(ConnectionInterface $connection,
        OverpassGrammar $grammar/*,
        Processor $processor*/) {
        $this->grammar = $grammar;
        //$this->processor = $processor;
        $this->connection = $connection;
    }

    protected function assertValidElement($element) {
        if (!in_array($element = strtolower((string)$element), $this->elements)) {
            throw new InvalidArgumentException('Illegal element.');
        }

        return $element;
    }

    protected function assertElementWheres($element) {
        if (is_null($element) && is_null($this->lastElement)) {
            throw new InvalidArgumentException('Unknown element for constraint.');
        }

        return is_null($element) ? $this->lastElement : $this->toWheresIfNotIn($element);
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * @param  string $operator
     * @param  mixed  $value
     *
     * @return void
     */
    protected function assertValidTagOperator($operator) {
        if (!in_array($operator, $this->tagOperators)) {
            throw new InvalidArgumentException('Illegal operator.');
        }
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * @param  string $operator
     * @param  mixed  $value
     *
     * @return void
     */
    protected function assertValidOrder($order) {
        if (!in_array($order, $this->orders)) {
            throw new InvalidArgumentException('Illegal order.');
        }
    }

    protected function toWheresIfNotIn($element) {
        $element = $this->assertValidElement($element);

        if (!array_key_exists($element, $this->wheres)) {
            $this->wheres[ $element ] = [
                'Id' => null,
                'BBox' => null,
                'Tag' => null,
            ];
        }

        return $element;
    }

    protected function settingsOption($name, $value) {
        $this->settings[] = compact('name', 'value');

        return $this;
    }

    public function getSettings() {
        return $this->settings;
    }

    public function getWheres() {
        return $this->wheres;
    }

    public function getOut() {
        return $this->out;
    }

    public function asJson() {
        return $this->settingsOption('out', 'json');
    }

    public function asXml() {
        return $this->settingsOption('out', 'xml');
    }

    public function timeout($value) {
        return $this->settingsOption('timeout', (int)$value);
    }

    public function maxsize($value) {
        return $this->settingsOption('maxsize', (int)$value);
    }

    public function bbox($south, $west, $north, $east) {
        return $this->settingsOption('bbox', $this->getGrammar()->prepareBBox($south, $west, $north, $east, true));
    }

    public function element($element) {

        $this->lastElement = $this->toWheresIfNotIn($element);

        return $this;
    }

    public function whereId($value, $element = null) {
        $element = $this->assertElementWheres($element);

        $type = 'Id';

        $this->wheres[ $element ][ $type ] = compact('value');

        return $this;
    }

    public function whereInBBox($south, $west, $north, $east, $element = null) {
        $element = $this->assertElementWheres($element);

        $type = 'BBox';

        $this->wheres[ $element ][ $type ] = compact('south', 'west', 'north', 'east');

        return $this;
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array $tag
     * @param  string       $operator
     * @param  string       $value
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereTag($tag, $operator = null, $value = null, $element = null) {
        $element = $this->assertElementWheres($element);

        if (is_array($tag)) {
            foreach ($tag as $key => $value) {
                $this->whereTag($key, '=', $value, $element);
            }
        }/* else if ($tag instanceof TagExpression) {
            @todo add TagExpression to support queries like: Key/value matches regular expression (~"key regex"~"value regex")
        }*/

        if (func_num_args() == 2) {
            list($value, $operator) = [$operator, '='];
        }

        $this->assertValidTagOperator($operator);

        if ($operator == "exists") {
            return $this->whereTagExists($tag, $element);
        }

        // If the value is "null", we will just assume the developer wants to add a
        // where empty tag clause to the query.
        if (is_null($value)) {
            return $this->whereTagEmpty($tag, $element);
        }

        $type = 'Tag';

        $this->wheres[ $element ][ $type ] = compact('tag', 'operator', 'value');

        return $this;
    }

    public function whereTagEmpty($tag, $element = null) {
        $element = $this->assertElementWheres($element);

        $type = 'Tag';

        $operator = "~";

        $value = "^$";

        $this->wheres[ $element ][ $type ] = compact('tag', 'operator', 'value');

        return $this;
    }

    public function whereTagExists($tag, $element = null) {
        $element = $this->assertElementWheres($element);

        $type = 'Tag';

        $operator = "exists";

        $value = null;

        $this->wheres[ $element ][ $type ] = compact('tag', 'operator', 'value');

        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string $column
     * @param  string $direction
     *
     * @return $this
     */
    public function orderBy($order) {
        $this->assertValidOrder($order);

        $this->out['order'] = $order;

        return $this;
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     *
     * @return \KageNoNeko\OSM\Query\Builder|static
     */
    public function orderById() {
        return $this->orderBy('asc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     *
     * @return \KageNoNeko\OSM\Query\Builder|static
     */
    public function orderByQt() {
        return $this->orderBy('qt');
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int $value
     *
     * @return $this
     */
    public function limit($value) {

        if ($value > 0) {
            $this->out['limit'] = (int)$value;
        }

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int $value
     *
     * @return \KageNoNeko\OSM\Query\Builder|static
     */
    public function take($value) {
        return $this->limit($value);
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toQl() {
        return $this->grammar->compileQuery($this);
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  int   $id
     * @param  array $columns
     *
     * @return mixed|static
     */
    public function find($element, $id) {
        return $this->whereId($id, $element)->first();
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array $columns
     *
     * @return mixed|static
     */
    public function first() {
        $results = $this->take(1)->get();

        return count($results) > 0 ? reset($results) : null;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     *
     * @return array|static[]
     */
    public function get() {

        return /*$this->processor->processResults($this, */$this->runQuery()/*)*/;
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runQuery() {
        return $this->connection->runQuery($this->toQl());
    }

    /**
     * Get the database connection instance.
     *
     * @return \KageNoNeko\OSM\ConnectionInterface
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Get the database query processor instance.
     *
     * @return \KageNoNeko\OSM\Query\Processors\Processor
     */
    /*public function getProcessor() {
        return $this->processor;
    }*/

    /**
     * Get the query grammar instance.
     *
     * @return \KageNoNeko\OSM\Query\Grammars\OverpassGrammar
     */
    public function getGrammar() {
        return $this->grammar;
    }
}
