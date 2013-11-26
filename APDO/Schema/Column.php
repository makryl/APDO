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
class Column
{

    /**
     * @var Table
     */
    public $table;
    public $name;

    protected $validators = [];

    public function value(Row $row)
    {
        $value = $row->{$this->name};
        foreach ($this->validators as $validator) {
            $value = $validator($value, $row, $this);
        }
        return $value;
    }

    /**
     * @param \callback $callback
     * @return \static
     */
    public function addValidator($callback)
    {
        $this->validators [] = $callback;
        return $this;
    }

    /**
     * @return \static
     */
    public function filter($filter, $options = null)
    {
        if (!is_array($options)) {
            $options = ['flags' => $options];
        }
        if (!isset($options['options']['default'])) {
            $options['options']['default'] = null;
        }
        return $this->addValidator(function($value) use ($filter, $options) {
            return filter_var($value, $filter, $options);
        });
    }

    public static $filter_error_message;

    /**
     * @throws StringEmailException
     * @return \static
     */
    public function filterStrict($filter, $options = null, $error_message = null)
    {
        if (!is_array($options)) {
            $options = ['flags' => $options];
        }
        if (!isset($options['options']['default'])) {
            $options['options']['default'] = null;
        }
        if (!isset($error_message)) {
            $error_message = self::$filter_error_message;
        }
        return $this->addValidator(function($value, $row) use ($filter, $options, $error_message) {
            if (isset($value)) {
                $value = filter_var($value, $filter, $options);
                if (!isset($value)) {
                    throw new ColumnValidatorException($row, $this, $error_message);
                }
            }
            return $value;
        });
    }

    /**
     * @return \static
     */
    public function required()
    {
        return $this->addValidator(function ($value, $row) {
            if (
                empty($value)
                && $value !== 0
                && $value !== 0.
                && $value !== false
            ) {
                throw new ColumnRequiredException($row, $this);
            }
            return $value;
        });
    }

    /**
     * @return \static
     */
    function emptySkip()
    {
        return $this->addValidator(function($value)
        {
            if (empty($value))
            {
                throw new ColumnSkipException();
            }
            return $value;
        });
    }

    /**
     * @return \static
     */
    function fkey()
    {
        return $this->addValidator(function($value, $row)
        {
            $rtable = $this->table->rfkey[$this->name];
            return isset($row->{$rtable}) ? $row->{$rtable}->pkey() : $value;
        });
    }

}

class ColumnValidatorException extends \Exception
{

    /**
     * @var \aeqdev\APDO\Schema\Row
     */
    public $row;

    /**
     * @var \aeqdev\APDO\Schema\Column
     */
    public $column;

    public function __construct(Row $row, Column $column, $message, $code, $previous)
    {
        parent::__construct($message, $code, $previous);
        $this->row = $row;
        $this->column = $column;
    }

}

class ColumnRequiredException extends ColumnValidatorException {}
