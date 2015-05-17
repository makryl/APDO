<?php

namespace aeqdev\APDO\Schema;

/**
 * Schema row object.
 * Validates and saves values into database.
 */
class Row implements \ArrayAccess
{

    /**
     * @var Table
     */
    public $table;

    protected $_new;
    protected $_data;
    protected $_refs;

    /**
     * PDO calls setters before constructor.
     * Before constructor this property is not set, in this case data stored directly in array without setters.
     * Existing of this property after constructor, indicate that row is ready to collect data for next save.
     *
     * @var array
     */
    protected $_needSaveData;

    /**
     * @param Table $table Schema table.
     * @param bool $new If this flag is set to TRUE, row will be inserted on first save.
     * @param array $data Cell values of row.
     */
    public function __construct(Table $table, $new = false, $data = null)
    {
        $this->table = $table;
        $this->_new = $new;
        $this->_needSaveData = [];
        if (isset($data)) {
            $this->_data = $data;
        }
    }

    /**
     * Allows serialize only cell values and new status.
     *
     * @return array
     */
    public function __sleep()
    {
        return [
            '_new',
            '_data',
        ];
    }

    public function __wakeup()
    {
        $this->_needSaveData = [];
    }

    /**
     * Returns primary key of row.
     * Complex keys comma separated.
     *
     * @return string
     */
    public function __toString()
    {
        if (is_array($this->table->pkey)) {
            return implode(', ', $this->pkey());
        } else {
            return (string)$this->pkey();
        }
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

    public function __set($name, $value)
    {
        if (isset($this->_needSaveData)) {
            if (isset($this->table->cols[$name])) {
                /** @var Column $col */
                $col = $this->table->{$name}();
                $this->_data[$name] = $col->filterSetValue($value, $this);
                $this->_needSaveData[$name] = $name;
            }
        } else {
            $this->_data[$name] = $value;
        }
    }

    public function __get($name)
    {
        if (isset($this->table->cols[$name])) {
            /** @var Column $col */
            $col = $this->table->{$name}();
            return $col->filterGetValue(isset($this->_data[$name]) ? $this->_data[$name] : null, $this);
        } else {
            return null;
        }
    }

    /**
     * @return bool True if row is new and not yet saved to database.
     */
    public function isNew()
    {
        return $this->_new;
    }

    /**
     * Set cell values of row.
     *
     * @param array $data Cell values of row.
     * @throws RowValidateException
     */
    public function data($data)
    {
        $exceptions = [];
        foreach ($data as $name => $value) {
            try {
                $this->__set($name, $value);
            } catch (ColumnSkipException $e) {
                continue;
            } catch (\Exception $e) {
                $exceptions[$name] = $e;
                continue;
            }
        }
        if (!empty($exceptions)) {
            throw new RowValidateException($this, $exceptions);
        }
    }

    /**
     * Saves needed cells into database.
     *
     * @throws RowValidateException
     */
    public function save()
    {
        $values = [];
        foreach ($this->_needSaveData as $name) {
            $values[$name] = $this->_data[$name];
        }
        if ($this->_new) {
            $pkey = $this->table->statement()
                ->insert($values);
            if (isset($pkey)) {
                $this->_data[$this->table->pkey] = $pkey;
            }
            $this->_new = false;
        } else if (!empty($values)) {
            $this->table->statement()
                ->key($this->pkey())
                ->update($values);
        }
        $this->_needSaveData = [];
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
            foreach ($this->table->pkey as $name) {
                $pkey []= $this->_data[$name];
            }
            return $pkey;
        } else {
            return $this->_data[$this->table->pkey];
        }
    }

    /**
     * Implemented ArrayAccess
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Implemented ArrayAccess
     * @param mixed $offset
     * @return Row
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * Implemented ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * Implemented ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
        unset($this->_needSaveData[$offset]);
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
