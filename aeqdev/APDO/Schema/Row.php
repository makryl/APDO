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

/**
 * Schema row object.
 * Validates and saves values into database.
 */
class Row
{

    /**
     * @var \aeqdev\APDO\Schema\Table
     */
    public $table;
    public $values;

    protected $new;

    /**
     * @param \aeqdev\APDO\Schema\Table $table Schema table.
     * @param bool $new If this flag is set to TRUE, row will be inserted on first save.
     */
    public function __construct(Table $table, $new = false)
    {
        $this->table = $table;
        $this->new = $new;
    }

    /**
     * If column with specified name exists, returns validated value of that column.
     * Otherwise, tries to perform appropriate refs selection.
     *
     * @param string $name
     * @param null $args
     * @return \aeqdev\APDO\Schema\Row|\aeqdev\APDO\Schema\Statement
     */
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
                ? $statement
                : $statement->fetchOne();
        }
    }

    /**
     * Validates and saves values into database.
     */
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

    /**
     * @return int|string|array Primary key of current row.
     */
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

    /**
     * @return array Array of validated values of current row with column names as keys.
     * @throws \aeqdev\APDO\Schema\RowValidateException
     */
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
            throw new RowValidateException($this, $exceptions);
        }
        return $this->values;
    }

}

/**
 * Row values validate exception.
 * Contains array of exceptions throwed during validation of cells in row.
 */
class RowValidateException extends \Exception
{

    /**
     * @var \aeqdev\APDO\Schema\Row
     */
    public $row;

    /**
     * @var \aeqdev\APDO\Schema\ColumnValidatorException[]
     */
    public $exceptions;

    public function __construct(Row $row, $exceptions, $message = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->row = $row;
        $this->exceptions = $exceptions;
    }

}
