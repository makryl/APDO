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

namespace aeqdev\APDO;

use \aeqdev\APDO\Schema\Statement;

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
        return new Statement($this->parameters, $statement, $args);
    }

}
