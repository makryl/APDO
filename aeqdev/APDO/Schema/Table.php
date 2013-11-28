<?php

/*
 * http://aeqdev.com/tools/php/apdo/
 * v 0.1
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
 *
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

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

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

    public function statement()
    {
        return $this->schema->statement()->schemaTable($this);
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