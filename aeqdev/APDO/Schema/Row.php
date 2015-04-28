<?php

namespace aeqdev\APDO\Schema;

/**
 * Schema row object.
 * Validates and saves values into database.
 */
class Row
{

    /**
     * @var Table
     */
    public $table;

    protected $_new;

    /**
     * @param Table $table Schema table.
     * @param bool $new If this flag is set to TRUE, row will be inserted on first save.
     * @param array $values Field values of row.
     */
    public function __construct(Table $table, $new = false, $values = [])
    {
        $this->table = $table;
        $this->_new = $new;
        $this->data($values);
    }

    /**
     * Tries to create appropriate refs statement, and fetch object if needed.
     *
     * @param string $name
     * @param null $args
     * @return null|Row|Statement Reference statement,
     *                            or object for one to one reference,
     *                            or null if no foreign key found.
     */
    public function __call($name, $args)
    {
        return Statement::refs($this, $name);
    }

    /**
     * Allows serialize only cell values.
     *
     * @return array
     */
    public function __sleep()
    {
        return $this->table->cols;
    }

    /**
     * @return bool True if row is new and not yet saved to database.
     */
    public function isNew()
    {
        return $this->_new;
    }

    /**
     * Validates and saves values into database.
     *
     * @param array $columns Array of needed columns.
     * @throws RowValidateException
     */
    public function save($columns = null)
    {
        $values = $this->values($columns);
        if ($this->_new) {
            $pkey = $this->table->statement()
                ->insert($values);
            if (isset($pkey)) {
                $this->{$this->table->pkey} = $pkey;
            }
            $this->_new = false;
        } else {
            $this->table->statement()
                ->key($this->pkey())
                ->update($values);
        }
        $this->data($values);
    }

    /**
     * Set field values of row.
     *
     * @param array $values Field values of row.
     */
    public function data($values)
    {
        foreach ($values as $name => $value) {
            if (isset($this->table->cols[$name])) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * Set field values and saves row.
     *
     * @param array $values Field values of row.
     */
    public function saveData($values)
    {
        $this->data($values);
        $this->save(array_keys($values));
    }

    /**
     * Deletes row from database.
     */
    public function delete()
    {
        if (!$this->_new) {
            $this->table->statement()
                ->key($this->pkey())
                ->delete();
        }
    }

    /**
     * Gets primary key value.
     *
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
     * Get array of validated field values.
     *
     * @param array $columns Array of needed columns.
     * @throws RowValidateException
     * @return array Array of validated values of current row with column names as keys.
     */
    public function values($columns = null)
    {
        $values = [];
        $exceptions = [];
        if (isset($columns)) {
            $columns = array_flip($columns);
        }
        foreach ($this->table->cols as $name) {
            if (!isset($columns) || isset($columns[$name])) {
                try {
                    $values[$name] = $this->table->{$name}()->value($this);
                } catch (ColumnSkipException $e) {
                    continue;
                } catch (\Exception $e) {
                    $exceptions[$name] = $e;
                    continue;
                }
            }
        }
        if (!empty($exceptions)) {
            throw new RowValidateException($this, $exceptions);
        }
        return $values;
    }

}

/**
 * Row values validate exception.
 * Contains array of exceptions thrown during validation of cells in row.
 */
class RowValidateException extends \Exception
{

    /**
     * @var Row
     */
    public $row;

    /**
     * @var ColumnValidatorException[]
     */
    public $exceptions;

    /**
     * @param Row $row
     * @param array $exceptions
     * @param string $message
     * @param int $code
     * @param null $previous
     */
    public function __construct(Row $row, $exceptions, $message = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->row = $row;
    $this->exceptions = $exceptions;
    }

    public function __toString() {
        $s = '';
        foreach ($this->exceptions as $e) {
            $s .= $e;
        }
        return $s;
    }

}
