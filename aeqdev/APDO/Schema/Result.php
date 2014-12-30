<?php

namespace aeqdev\APDO\Schema;

/**
 * Schema result can work with schema table references.
 * Implements ArrayAccess, Iterator, Countable
 */
class Result implements \ArrayAccess, \Iterator, \Countable
{

    /**
     * @var Table
     */
    public $table;

    /**
     * @var Row[]
     */
    public $result;

    private $position = 0;

    /**
     * @param Table $table Schema table.
     * @param $result Row[] Data
     */
    function __construct(Table $table, $result)
    {
        $this->table = $table;
        $this->result = $result;
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
     * Implemented Iterator
     */
    public function &current()
    {
        return $this->result[$this->position];
    }

    /**
     * Implemented Iterator
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Implemented Iterator
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Implemented Iterator
     */
    public function valid()
    {
        return isset($this->result[$this->position]);
    }

    /**
     * Implemented Iterator
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Implemented ArrayAccess
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->result[$offset]);
    }

    /**
     * Implemented ArrayAccess
     * @param mixed $offset
     * @return Row
     */
    public function &offsetGet($offset)
    {
        return $this->result[$offset];
    }

    /**
     * Implemented ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (isset($offset)) {
            $this->result[$offset] = $value;
        } else {
            $this->result []= $value;
        }
    }

    /**
     * Implemented ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->result[$offset]);
    }

    /**
     * Implemented Countable
     */
    public function count()
    {
        return count($this->result);
    }
}
