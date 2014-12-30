<?php

namespace aeqdev\APDO;

use aeqdev\APDO;
use aeqdev\APDO\Schema\Table;

/**
 * Represents database schema.
 * Contains information about tables.
 */
class Schema extends APDO
{

    public $prefix;

    protected $tables = [];

    /**
     * Creates table object with specified name, if it is not already exists, and returns it.
     *
     * @param string $name Table name
     * @return Table Table object
     */
    public function __get($name)
    {
        if (!isset($this->tables[$name])) {
            $property = 'class_' . $name;
            if (property_exists($this, $property)) {
                $classTable = $this->{$property};
                $this->tables[$name] = new $classTable($this);
            } else {
                return null;
            }
        }
        return $this->tables[$name];
    }

    /**
     * Creates statement for specified table.
     *
     * @param string $name Table name.
     * @param null $args
     * @return Schema\Statement Statement for specified table.
     */
    public function __call($name, $args)
    {
        /* @var $table Table */
        $table = $this->{$name};
        return isset($table) ? $table->statement() : null;
    }

    /**
     * Creates new statement.
     *
     * @param string $statement SQL statement.
     * @param string|array $args Argument or array of arguments for the statement.
     * @return Schema\Statement Created statement.
     */
    public function statement($statement = null, $args = null)
    {
        return new Schema\Statement($this->options, $statement, $args);
    }

}
