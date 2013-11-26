<?php

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

    public function __constructor(Table $table, $name)
    {
        $this->table = $table;
        $this->name = $name;
    }

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
        if (!isset($options['default'])) {
            $options['default'] = null;
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
        if (!isset($options['default'])) {
            $options['default'] = null;
        }
        if (!isset($error_message)) {
            $error_message = self::$filter_error_message;
        }
        return $this->addValidator(function($value) use ($filter, $options, $error_message) {
            if (isset($value)) {
                $value = filter_var($value, $filter, $options);
                if (!isset($value)) {
                    throw new ColumnValidatorException($this, $error_message);
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
        return $this->addValidator(function ($value) {
            if (
                empty($value)
                && $value !== 0
                && $value !== 0.
                && $value !== false
            ) {
                throw new ColumnRequiredException($this);
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
     * @var \aeqdev\APDO\Schema\Column
     */
    public $column;

    public function __construct(Column $column, $message, $code, $previous)
    {
        parent::__construct($message, $code, $previous);
        $this->column = $column;
    }

}

class ColumnRequiredException extends ColumnValidatorException {}
