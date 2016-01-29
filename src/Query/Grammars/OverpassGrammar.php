<?php

namespace KageNoNeko\OSM\Query\Grammars;

use KageNoNeko\OSM\Query\OverpassBuilder as QueryBuilder;

class OverpassGrammar
{

    protected $components = [
        'settings',
        'wheres',
        'out',
    ];

    protected function compileSettingsOption($option) {
        return "[{$option['name']}:{$option['value']}]";
    }

    protected function compileSettings(QueryBuilder $query) {
        if (!$settings = $query->getSettings()) {
            return '';
        }

        $ql = implode('', array_map([$this, 'compileSettingsOption'], $settings));

        return $ql;
    }

    protected function compileWhereId(array $where) {
        return "(" . intval($where['value']) . ")";
    }

    protected function compileWhereBBox(array $where) {
        return "({$where['bbox']})";
    }

    protected function compileWhereTag(array $where) {
        $tag = addslashes($where['tag']);
        $value = addslashes($where['value']);
        if ($where['operator'] == "exists") {
            return "[\"{$tag}\"]";
        }

        return "[\"{$tag}\"{$where['operator']}\"{$value}\"]";
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

    protected function compileOut(QueryBuilder $query) {
        $out = $query->getOut();

        return "out " . implode(' ', array_filter($out, function ($value) {
            return (string)$value !== '';
        }));
    }

    protected function compileComponents(QueryBuilder $query) {
        $ql = [];

        foreach ($this->components as $component) {
            $method = 'compile' . ucfirst($component);
            $ql[ $component ] = $this->{$method}($query);
        }

        return $ql;
    }

    protected function concatenate($segments) {
        return implode(';', array_filter($segments, function ($value) {
            return (string)$value !== '';
        })) . ";";
    }

    public function compileQuery(QueryBuilder $query) {

        $ql = trim($this->concatenate($this->compileComponents($query)));

        return $ql;
    }
}
