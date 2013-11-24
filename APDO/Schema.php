<?php

namespace aeqdev\APDO;

/**
 *
 */
class Schema extends \aeqdev\APDO
{

    public $prefix;

    protected $tables = [];

    public function __get($name)
    {
        if (!isset($this->tables[$name])) {
            $classTable = $this->{'class_' . $name};
            $this->tables[$name] = new $classTable($this);
        }
        return $this->tables[$name];
    }

    public function __call($name, $args)
    {
        return $this->{$name}->statement();
    }

    public function statement($statement = null, $args = null)
    {
        return new tatement($this->parameters, $statement, $args);
    }

}
