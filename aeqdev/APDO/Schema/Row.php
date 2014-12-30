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
    public $values;

    protected $__new;

    /**
     * @param Table $table Schema table.
     * @param bool $new If this flag is set to TRUE, row will be inserted on first save.
     */
    public function __construct(Table $table, $new = false)
    {
        $this->table = $table;
        $this->__new = $new;
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
     * Validates and saves values into database.
     * @param array $columns Array of needed columns.
     * @throws RowValidateException
     */
    public function save($columns = null)
    {
        if ($this->__new) {
            $pkey = $this->table->statement()
                ->insert($this->values($columns));
            if (isset($pkey)) {
                $this->{$this->table->pkey} = $pkey;
            }
            $this->__new = false;
        } else {
            $this->table->statement()
                ->key($this->pkey())
                ->update($this->values($columns));
        }
    }

    /**
     * Deletes row from database.
     */
    public function delete()
    {
        if (!$this->__new) {
            $this->table->statement()
                ->key($this->pkey())
                ->delete();
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
     * @param array $columns Array of needed columns.
     * @throws RowValidateException
     * @return array Array of validated values of current row with column names as keys.
     */
    public function values($columns = null)
    {
        $this->values = [];
        $exceptions = [];
        if (isset($columns)) {
            $columns = array_flip($columns);
        }
        foreach ($this->table->cols as $name) {
            if (!isset($columns) || isset($columns[$name])) {
                try {
                    $this->values[$name] = $this->table->{$name}()->value($this);
                } catch (ColumnSkipException $e) {
                    continue;
                } catch (\Exception $e) {
                    $exceptions[$name] = $e;
                    break;
                }
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
