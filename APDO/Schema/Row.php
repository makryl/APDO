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

/**
 *
 */
class Row
{

    public $table;
    public $values;

    protected $new;

    public function table()
    {
        return $this->table;
    }

    public function __construct(Table $table, $new = false)
    {
        $this->table = $table;
        $this->new = $new;
    }

    public function __call($name, $args)
    {
        if (isset($this->table->cols[$name])) {
            return $this->table->{$name}()->value($this);
        } else {
            /* @var $statement \aeqdev\APDO\Schema\Statement */
            $statement = $this->table->schema->{$name}()->refs($this);
            /* @var $refTable \aeqdev\APDO\Schema\Table */
            $refTable = $this->table->schema->{$name};

            return isset($refTable->fkey[$this->table->name])
                && !isset($refTable->ukey[$refTable->fkey[$this->table->name]])
                ? $statement->fetchAll()
                : $statement->fetchOne();
        }
    }

    public function save()
    {
        if ($this->new) {
            $pkey = $this->table->statement()
                ->insert($this->values());
            if (isset($pkey)) {
                $this->{$this->table->pkey} = $pkey;
            }
            $this->new = false;
        } else {
            $this->table->statement()
                ->key($this->pkey())
                ->update($this->values());
        }
    }

    public function pkey()
    {
        if (is_array($this->table->pkey)) {
            $pkey = [];
            foreach ($this->table->pkey as $field) {
                $pkey []= $this->{$field};
            }
            return $pkey;
        } else {
            return $this->{$this->table->pkey};
        }
    }

    public function values()
    {
        $this->values = [];
        $exceptions = [];
        foreach ($this->table->cols as $name) {
            try {
                $this->values[$name] = $this->table->{$name}()->value($this);
            } catch (ColumnSkipException $e) {
                continue;
            } catch (\Exception $e) {
                $exceptions[$name] = $e;
                break;
            }
        }
        if (!empty($exceptions)) {
            throw new RowValidateException($exceptions);
        }
        return $this->values;
    }

}

class ColumnSkipException extends \Exception {}

class RowValidateException extends \Exception
{

    public $exceptions;

    public function __construct($exceptions, $message = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->exceptions = $exceptions;
    }

}
