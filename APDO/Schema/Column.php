<?php

namespace aeqdev\APDO\Schema;

/**
 *
 */
class Column
{

    public $name;

    protected $validators = [];

    public function value(Row $row)
    {
        $value = $row->{$this->name};
        foreach ($this->validators as $validator) {
            $value = $validator($value, $this->name, $row);
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
     * @throws StringEmailException
     * @return \static
     */
    public function filterVar($filter, $options = null, $errorMessage = null)
    {
        if (!isset($options['default'])) {
            $options['default'] = null;
        }
        return $this->addValidator(function($value) use ($filter, $options, $errorMessage) {
            return isset($value) ? filter_var($value, $filter, $options) : null;
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
    function fkey($table)
    {
        return $this->addValidator(function($value, $name, $row) use ($table)
        {
            return isset($row->{$table}) ? $row->{$table}->pkey() : $value;
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
class ColumnFilterVarException extends ColumnValidatorException {}
