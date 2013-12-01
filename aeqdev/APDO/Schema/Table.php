<?php

/*
 * http://aeqdev.com/tools/php/apdo/
 * v 0.2
 *
 * Copyright © 2013 Krylosov Maksim <Aequiternus@gmail.com>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

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
     * @var \aeqdev\APDO\Schema
     */
    public $schema;

    public $name;
    public $cols;
    public $pkey;
    public $ukey;
    public $fkey;
    public $rtable;
    public $class_row;

    protected $columns;

    /**
     * @param \aeqdev\APDO\Schema $schema Schema.
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
     * @return null|\aeqdev\APDO\Schema\Column Column object
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
    }

    /**
     * Creates table statement.
     *
     * @return \aeqdev\APDO\Schema\Statement Table statement.
     */
    public function statement()
    {
        return $this->schema->statement()->schemaTable($this);
    }

    /**
     * Creates table row.
     *
     * @return \aeqdev\APDO\Schema\Row Table row object.
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
     * @return null|\aeqdev\APDO\Schema\Row Table row object with specified prymary key or null if no row found.
     */
    public function get($pkey)
    {
        return $this->statement()
            ->key($pkey)
            ->fetchOne();
    }

}