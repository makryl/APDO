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

namespace aeqdev\APDO;

use \aeqdev\APDO\Schema\Statement;

/**
 * Represents database schema.
 * Contains information about tables.
 */
class Schema extends \aeqdev\APDO
{

    public $prefix;

    protected $tables = [];

    /**
     * Creates table object with specified name, if it is not already exists, and returns it.
     *
     * @param string $name Table name
     * @return \aeqdev\APDO\Schema\Table Table object
     */
    public function __get($name)
    {
        if (!isset($this->tables[$name])) {
            $classTable = $this->{'class_' . $name};
            $this->tables[$name] = new $classTable($this);
        }
        return $this->tables[$name];
    }

    /**
     * Creates statement for specified table.
     *
     * @param string $name Table name.
     * @param null $args
     * @return \aeqdev\APDO\Schema\Statement Statement for specified table.
     */
    public function __call($name, $args)
    {
        return $this->{$name}->statement();
    }

    /**
     * Creates new statement.
     *
     * @param string $statement SQL statement.
     * @param string|array $args Argument or array of arguments for the statement.
     * @return \aeqdev\APDO\Schema\Statement Created statement.
     */
    public function statement($statement = null, $args = null)
    {
        return new Statement($this->parameters, $statement, $args);
    }

}
