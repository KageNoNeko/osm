<?php

namespace KageNoNeko\OSM\Query;

use InvalidArgumentException;
use KageNoNeko\OSM\BoundingBox;
use KageNoNeko\OSM\ConnectionInterface;
use KageNoNeko\OSM\Query\Grammars\OverpassGrammar;
//use KageNoNeko\OSM\Query\Processors\Processor;

class OverpassBuilder
{

    /**
     * The OSM API connection instance.
     *
     * @var \KageNoNeko\OSM\ConnectionInterface
     */
    protected $connection;

    /**
     * The OSM API query grammar instance.
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
     * The settings constraints for the query.
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
     * The out constraints for the query.
     *
     * @var array
     */
    protected $out = [
        'limit' => null,
        'order' => null,
        'verbosity' => null,
    ];

    /**
     * All of the available elements.
     *
     * @var array
     */
    protected $elements = [
        'node', 'way', 'area', 'rel',
    ];

    /**
     * All of the available orders.
     *
     * @var array
     */
    protected $orders = [
        'asc', 'qt',
    ];

    /**
     * All of the available verbosity types.
     *
     * @var array
     */
    protected $verbosity = [
        'ids', 'skel', 'body', 'tags', 'meta',
    ];

    /**
     * All of the available tag constraint operators.
     *
     * @var array
     */
    protected $tagOperators = [
        '=', '!=', '~', '!~', 'exists',
    ];

    /**
     * Last constraint's element.
     *
     * @var string
     */
    protected $lastElement;

    protected function assertValidElement($element) {
        if (!in_array($element = strtolower((string)$element), $this->elements)) {
            throw new InvalidArgumentException('Illegal element.');
        }

        return $element;
    }

    protected function assertValidTagOperator($operator) {
        if (!in_array($operator, $this->tagOperators)) {
            throw new InvalidArgumentException('Illegal operator.');
        }
    }

    protected function assertValidOrder($order) {
        if (!in_array($order, $this->orders)) {
            throw new InvalidArgumentException('Illegal order.');
        }
    }

    protected function assertValidVerbosity($verbosity) {
        if (!in_array($verbosity, $this->verbosity)) {
            throw new InvalidArgumentException('Illegal verbosity.');
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

    protected function assertElementWheres($element) {
        if (is_null($element) && is_null($this->lastElement)) {
            throw new InvalidArgumentException('Unknown element for constraint.');
        }

        return is_null($element) ? $this->lastElement : $this->toWheresIfNotIn($element);
    }

    protected function settingsOption($name, $value) {
        $this->settings[] = compact('name', 'value');

        return $this;
    }

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

    public function getConnection() {
        return $this->connection;
    }

    public function getGrammar() {
        return $this->grammar;
    }

    /*public function getProcessor() {
        return $this->processor;
    }*/

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

    public function bbox($bBoxOrSouth, $west = null, $north = null, $east = null) {
        if (!$bBoxOrSouth instanceof BoundingBox) {
            if (func_num_args() != 4) {
                throw new \InvalidArgumentException("Method accepts either \\KageNoNeko\\OSM\\BoundingBox instance either for coordinates.");
            }
            $bBoxOrSouth = new BoundingBox($bBoxOrSouth, $west, $north, $east);
        }
        return $this->settingsOption('bbox', $bBoxOrSouth);
    }

    public function element($element) {

        $this->lastElement = $this->toWheresIfNotIn($element);

        return $this;
    }

    public function whereId($value, $element = null) {
        $element = $this->assertElementWheres($element);

        $type = 'Id';

        $this->wheres[$element][$type] = compact('value');

        return $this;
    }

    public function whereInBBox($bBoxOrSouth, $elementOrWest = null, $north = null, $east = null, $element = null) {

        if (func_num_args() < 3) {
            if (!$bBoxOrSouth instanceof BoundingBox || !(is_string($elementOrWest) || is_null($elementOrWest))) {
                throw new \InvalidArgumentException("In shortcut method call first argument should be \\KageNoNeko\\OSM\\BoundingBox instance and second can be element name or should be omitted.");
            }
            $element = $elementOrWest;
        } else {
            $bBoxOrSouth = new BoundingBox($bBoxOrSouth, $elementOrWest, $north, $east);
        }

        $element = $this->assertElementWheres($element);

        $type = 'BBox';

        $this->wheres[$element][$type] = ['bbox' => $bBoxOrSouth];

        return $this;
    }

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

        $this->wheres[$element][$type] = compact('tag', 'operator', 'value');

        return $this;
    }

    public function whereTagEmpty($tag, $element = null) {
        $element = $this->assertElementWheres($element);

        $type = 'Tag';

        $operator = "~";

        $value = "^$";

        $this->wheres[$element][$type] = compact('tag', 'operator', 'value');

        return $this;
    }

    public function whereTagExists($tag, $element = null) {
        $element = $this->assertElementWheres($element);

        $type = 'Tag';

        $operator = "exists";

        $value = null;

        $this->wheres[$element][$type] = compact('tag', 'operator', 'value');

        return $this;
    }

    public function orderBy($order) {
        $this->assertValidOrder($order);

        $this->out['order'] = $order;

        return $this;
    }

    public function orderById() {
        return $this->orderBy('asc');
    }

    public function orderByQt() {
        return $this->orderBy('qt');
    }

    public function limit($value) {

        if ($value > 0) {
            $this->out['limit'] = (int)$value;
        }

        return $this;
    }

    public function take($value) {
        return $this->limit($value);
    }

    public function verbosity($verbosity) {
        $this->assertValidVerbosity($verbosity);

        $this->out['verbosity'] = $verbosity;

        return $this;
    }

    public function toQl() {
        return $this->grammar->compileQuery($this);
    }

    protected function runQuery() {
        return $this->connection->runQuery($this->toQl());
    }

    public function get() {
        return /*$this->processor->processResults($this, */$this->runQuery()/*)*/;
    }

    public function first() {
        $results = $this->take(1)->get();

        return count($results) > 0 ? reset($results) : null;
    }

    public function find($id, $element = null) {
        return $this->whereId($id, $element)->first();
    }
}
