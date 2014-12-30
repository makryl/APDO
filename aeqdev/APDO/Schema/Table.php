<?php

namespace aeqdev\APDO\Schema;

use \aeqdev\APDO\Schema;

/**
 * Represents table of schema.
 * Contains information about columns, primary keys, unique keys and foreign keys.
 * Creates statements and row objects for the table.
 */
class Table
{

    /**
     * @var Schema
     */
    public $schema;

    public $name;
    public $comment;
    public $cols;
    public $pkey;
    public $ukey;
    public $fkey;
    public $rkey;
    public $class_row;

    protected $columns;

    /**
     * @param Schema $schema Schema.
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Returns column object, if column with specified name exists.
     *
     * @param string $name Column name.
     * @param null $args
     * @return null|Column Column object
     */
    public function __call($name, $args)
    {
        if (isset($this->cols[$name])) {
            if (!isset($this->columns[$name])) {
                $column = $this->{'column_' . $name}();
                $column->table = $this;
                $column->name = $name;
                $this->columns[$name] = $column;
            }
            return $this->columns[$name];
        }
        return null;
    }

    /**
     * Creates table statement.
     *
     * @return Statement Table statement.
     */
    public function statement()
    {
        return $this->schema->statement()->schemaTable($this);
    }

    /**
     * Creates table row.
     *
     * @return Row Table row object.
     */
    public function create()
    {
        $classRow = $this->class_row;
        return new $classRow($this, true);
    }

    /**
     * Gets table row by primary key.
     *
     * @param int|string|array $pkey Primary key of row.
     * @return null|Row Table row object with specified prymary key or null if no row found.
     */
    public function get($pkey)
    {
        return $this->statement()
            ->key($pkey)
            ->fetchOne();
    }

}
