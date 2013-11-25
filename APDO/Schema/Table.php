<?php

namespace aeqdev\APDO\Schema;

/**
 *
 */
class Table
{

    /**
     * @var \aeqdev\APDO\Schema
     */
    public $schema;

    public $name;
    public $pkey;
    public $ukey;
    public $fkey;
    public $cols;

    protected $class_row;
    protected $columns;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function __call($name, $args)
    {
        if (isset($this->cols[$name])) {
            if (!isset($this->columns[$name])) {
                $this->columns[$name] = $this->{'column_' . $name}($this, $name);
            }
            return $this->columns[$name];
        }
    }

    public function statement() {
        return $this->schema->statement()
            ->schemaTable($this)
            ->fetchMode(\PDO::FETCH_CLASS, $this->class_row, [$this]);
    }

    public function create()
    {
        $classRow = $this->class_row;
        return new $classRow($this, true);
    }

    public function get($pkey)
    {
        return $this->statement()
            ->key($pkey)
            ->fetchOne();
    }

}