<?php

namespace aeqdev\APDO\Schema;

/**
 * Schema column object.
 * Contains validators for table column and performs validation of rows.
 */
class Column
{

    /**
     * @var Table
     */
    public $table;
    public $name;
    public $comment;
    public $null = true;

    protected $setFilters = [];
    protected $getFilters = [];

    /**
     * @param Table $table Table.
     * @param string $name Column name.
     */
    public function __construct(Table $table, $name)
    {
        $this->table = $table;
        $this->name = $name;
    }

    /**
     * Set comment for column.
     *
     * @param string $comment Comment.
     * @return static|$this Current column.
     */
    public function comment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Executes all set filters on value of specified row and returns filtered value.
     *
     * @param mixed $value Value to validate.
     * @param Row $row Row that setting value.
     * @return mixed Valid value.
     */
    public function filterSetValue($value, Row $row)
    {
        foreach ($this->setFilters as $filter) {
            $value = $filter($value, $row, $this);
        }
        return $value;
    }

    /**
     * Executes all get filters on value of specified row and returns filtered value.
     *
     * @param mixed $value Value to get.
     * @param Row $row Row that getting value.
     * @return mixed Value.
     */
    public function filterGetValue($value, Row $row)
    {
        foreach ($this->getFilters as $filter) {
            $value = $filter($value, $row, $this);
        }
        return $value;
    }

    /**
     * Adds filter for set operation to the column.
     *
     * @param callable $callback Filter function ($value, $row, $column)
     * @return static|$this Current column.
     */
    public function addSetFilter($callback)
    {
        $this->setFilters [] = $callback;
        return $this;
    }

    /**
     * Adds filter for get operation to the column.
     *
     * @param callable $callback Filter function ($value, $row, $column)
     * @return static|$this Current column.
     */
    public function addGetFilter($callback)
    {
        $this->setFilters [] = $callback;
        return $this;
    }

    /**
     * Adds filter_var validator to the column.
     * See http://php.net/manual/function.filter-var.php for details.
     *
     * @param int $filter Filter ID. Use FILTER_* constants.
     * @param int|array $options Filter options.
     * @return static|$this Current column.
     */
    public function filterVar($filter, $options = null)
    {
        if (!is_array($options)) {
            $options = ['flags' => $options];
        }
        if (!isset($options['options']['default'])) {
            $options['options']['default'] = null;
        }
        return $this->addSetFilter(function($value) use ($filter, $options) {
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
     * @throws ColumnValidatorException
     * @return static|$this Current column.
     */
    public function filterVarStrict($filter, $options = null, $error_message = null)
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
        return $this->addSetFilter(function($value, $row) use ($filter, $options, $error_message) {
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
     * @param string $error_message Error message on validation fail.
     * @throws ColumnRequiredException
     * @return static|$this Current column.
     */
    public function required($error_message = null)
    {
        $this->null = false;

        return $this->addSetFilter(function ($value, $row) use ($error_message) {
            if (
                empty($value)
                && $value !== 0
                && $value !== 0.
                && $value !== false
            ) {
                throw new ColumnRequiredException($row, $this, $error_message);
            }
            return $value;
        });
    }

    /**
     * Adds filter to the column, that throws skip exception for empty values.
     * Skipped columns will not passed to validated values.
     *
     * @throws ColumnSkipException
     * @return static|$this Current column.
     */
    public function emptySkip()
    {
        return $this->addSetFilter(function($value, $row) {
            if (empty($value)) {
                throw new ColumnSkipException($row, $this);
            }
            return $value;
        });
    }

    /**
     * Adds filter to the column, that throws skip exception for null values.
     * Skipped columns will not passed to validated values.
     *
     * @throws ColumnSkipException
     * @return static|$this Current column.
     */
    public function nullSkip()
    {
        return $this->addSetFilter(function($value, $row) {
            if (!isset($value)) {
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
     * @param string $error_message Error message on validation fail.
     * @return $this|static
     * @throws ColumnValidatorException
     */
    public function fkey($error_message = null)
    {
        return $this->addSetFilter(function($value, $row) use ($error_message) {
            /** @var $row Row */
            if ($value instanceof Row) {
                if ($value->table != $this->table->fkey[$this->name]) {
                    throw new ColumnValidatorException($row, $this, $error_message);
                }
            }
            return $value;
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
     * @var Row
     */
    public $row;

    /**
     * @var Column
     */
    public $column;

    public function __construct(Row $row, Column $column, $message = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->row = $row;
        $this->column = $column;
    }

    public function __toString()
    {
        $message = $this->message;
        $this->message = 'Validation failed for column "' . $this->column->name . '"'
            . ' in row "' . $this->row->pkey() . '"'
            . ' of table "' . $this->column->table->name . '"'
            . (empty($message) ? '' : ' with message "' . $message . '"');
        $string = parent::__toString();
        $this->message = $message;
        return $string;
    }

}

class ColumnRequiredException extends ColumnValidatorException {}
class ColumnSkipException extends ColumnValidatorException {}
