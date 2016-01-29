<?php

namespace KageNoNeko\OSM\Query\Grammars;

use KageNoNeko\OSM\Query\OverpassBuilder as QueryBuilder;

class OverpassGrammar
{
    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $components = [
        'settings',
        'wheres',
        'out',
    ];

    /**
     * Compile a select query into QL.
     *
     * @param  \KageNoNeko\OSM\Query\OverpassBuilder $query
     *
     * @return string
     */
    public function compileQuery(QueryBuilder $query) {

        $ql = trim($this->concatenate($this->compileComponents($query)));

        return $ql;
    }

    /**
     * Compile the components necessary for a select clause.
     *
     * @param  \KageNoNeko\OSM\Query\OverpassBuilder $query
     *
     * @return array
     */
    protected function compileComponents(QueryBuilder $query) {
        $ql = [];

        foreach ($this->components as $component) {
            $method = 'compile' . ucfirst($component);
            $ql[ $component ] = $this->{$method}($query);
        }

        return $ql;
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param  array $segments
     *
     * @return string
     */
    protected function concatenate($segments) {
        return implode(';', array_filter($segments, function ($value) {
            return (string)$value !== '';
        })) . ";";
    }

    protected function compileSettings(QueryBuilder $query) {
        if (!$settings = $query->getSettings()) {
            return '';
        }

        $ql = implode('', array_map([$this, 'compileSettingsOption'], $settings));

        return $ql;
    }

    protected function compileSettingsOption($option) {
        return "[{$option['name']}:{$option['value']}]";
    }

    protected function compileWheres(QueryBuilder $query) {
        $ql = [];

        if (!$wheres = $query->getWheres()) {
            return '';
        }

        foreach ($wheres as $element => $eWheres) {
            $eQl = [];
            foreach ($eWheres as $type => $where) {
                if (!is_null($where)) {
                    $method = "compileWhere{$type}";

                    $eQl[] = $this->$method($where);
                }
            }
            $ql[] = (string)$element . (count($eQl) > 0 ? implode('', $eQl) : "");
        }

        if (count($ql) > 1) {
            return "(" . implode(';', $ql) . ")";
        }

        return reset($ql);
    }

    public function prepareBBox($south, $west, $north, $east, $asList = false) {
        $bbox = [
            floatval($south),
            floatval($west),
            floatval($north),
            floatval($east),
        ];

        return ($asList ? implode(",", $bbox) : $bbox);
    }

    protected function compileWhereId(array $where) {
        return "(" . intval($where['value']) . ")";
    }

    protected function compileWhereBBox(array $where) {
        return "({$this->prepareBBox($where['south'], $where['west'], $where['north'], $where['east'], true)})";
    }

    protected function compileWhereTag(array $where) {
        $tag = addslashes($where['tag']);
        $value = addslashes($where['value']);
        if ($where['operator'] == "exists") {
            return "[\"{$tag}\"]";
        }

        return "[\"{$tag}\"{$where['operator']}\"{$value}\"]";
    }

    protected function compileOut(QueryBuilder $query) {
        $out = $query->getOut();

        return "out " . implode(' ', array_filter($out, function ($value) {
            return (string)$value !== '';
        }));
    }
}
