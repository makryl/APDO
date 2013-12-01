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
 * Schema column object.
 * Contains validators for table column and performs validation of rows.
 */
class Column
{

    /**
     * @var \aeqdev\APDO\Schema\Table
     */
    public $table;
    public $name;

    protected $validators = [];

    /**
     * Executes all validators on column value of specified row and returns result.
     *
     * @param \aeqdev\APDO\Schema\Row $row Row to extract raw value from.
     * @return mixed Valid value.
     */
    public function value(Row $row)
    {
        $value = $row->{$this->name};
        foreach ($this->validators as $validator) {
            $value = $validator($value, $row, $this);
        }
        return $value;
    }

    /**
     * Adds validator to the column.
     *
     * @param \callback $callback Validator function ($value, $row, $column)
     * @return \static Current column.
     */
    public function addValidator($callback)
    {
        $this->validators [] = $callback;
        return $this;
    }

    /**
     * Adds filter_var validator to the column.
     * See http://php.net/manual/function.filter-var.php for details.
     *
     * @param int $filter Filter ID. Use FILTER_* constants.
     * @param int|array $options Filter options.
     * @return \static Current column.
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
     * Adds filter_var validator to the column.
     * Throws exception if validation fails.
     * See http://php.net/manual/function.filter-var.php for details.
     *
     * @param int $filter Filter ID. Use FILTER_* constants.
     * @param int|array $options Filter options.
     * @param string $error_message Error message on validation fail.
     * @throws \aeqdev\APDO\Schema\ColumnValidatorException
     * @return \static Current column.
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
     * Adds required filter to the column.
     * Throws exception if value is empty and not integer 0, not float 0 and not boolean false.
     *
     * @throws \aeqdev\APDO\Schema\ColumnRequiredException
     * @return \static Current column.
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
     * Adds filter to the column, that throws skip exception for empty values.
     * Skipped columns will not passed to validated values.
     *
     * @throws \aeqdev\APDO\Schema\ColumnSkipException
     * @return \static Current column.
     */
    function emptySkip()
    {
        return $this->addValidator(function($value, $row)
        {
            if (empty($value))
            {
                throw new ColumnSkipException($row, $this);
            }
            return $value;
        });
    }

    /**
     * Adds filter to the column, that throws skip exception for null values.
     * Skipped columns will not passed to validated values.
     *
     * @throws \aeqdev\APDO\Schema\ColumnSkipException
     * @return \static Current column.
     */
    function nullSkip()
    {
        return $this->addValidator(function($value, $row)
        {
            if (!isset($value))
            {
                throw new ColumnSkipException($row, $this);
            }
            return $value;
        });
    }

    /**
     * Adds foreign key filter to the column.
     * This filter sets value of foreign key column from primary key of referenced data.
     * If referenced data not exists, column value used.
     *
     * @return \static Current column.
     */
    function fkey()
    {
        return $this->addValidator(function($value, $row)
        {
            $rtable = $this->table->rtable[$this->name];
            return isset($row->{$rtable}) ? $row->{$rtable}->pkey() : $value;
        });
    }

}

/**
 * Column validation exception.
 * Contains row and column objects.
 */
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
class ColumnSkipException extends ColumnValidatorException {}
