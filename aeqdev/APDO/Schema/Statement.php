<?php

namespace aeqdev\APDO\Schema;

/**
 * Schema statement can set schema table parameters and work with schema table references.
 */
class Statement extends \aeqdev\APDOStatement
{

    /**
     * @var \aeqdev\APDO\Schema\Table
     */
    protected $schemaTable;

    /**
     * Sets schema table for the statement.
     *
     * @param \aeqdev\APDO\Schema\Table $table Schema table.
     * @return static|$this Current statement.
     */
    public function schemaTable(Table $table)
    {
        $this->schemaTable = $table;
        return $this->table($table->schema->prefix . $table->name)
            ->pkey($table->pkey)
            ->fetchMode(\PDO::FETCH_CLASS, $table->class_row, [$table]);
    }

    /**
     * Adds conditions to SELECT statement for selecting referenced data.
     * This method chooses appropriate APDOStatement method
     * (one of: referrers, referrersUnique, references, referencesUnique)
     * using data passed in argument and foreign keys of tables.
     * If no foreign key found, marks statement as "nothing".
     *
     * @param array|object $data Data.
     * @return static|$this Current statement.
     */
    public function refs(&$data)
    {
        if (empty($data)) {
            return $this->nothing();
        } else if ($data instanceof Row) {
            $itemTable = $data->table;
        } else {
            $item = reset($data);
            if ($item instanceof Row) {
                $itemTable = $item->table;
            } else {
                return $this->nothing();
            }
        }

        if (isset($itemTable->fkey[$this->schemaTable->name])) {
            return $this->referrers(
                $data,
                $itemTable->name,
                $this->schemaTable->name,
                $itemTable->fkey[$this->schemaTable->name],
                $itemTable->pkey,
                isset($itemTable->ukey[$itemTable->fkey[$this->schemaTable->name]])
            );
        } else if (isset($this->schemaTable->fkey[$itemTable->name])) {
            return $this->references(
                $data,
                $this->schemaTable->name,
                $itemTable->name,
                $this->schemaTable->fkey[$itemTable->name],
                $itemTable->pkey,
                isset($this->schemaTable->ukey[$this->schemaTable->fkey[$itemTable->name]])
            );
        } else {
            return $this->nothing();
        }
    }

    /**
     * Creates statement on specified table with refs conditions,
     * using data fetched from the current statement.
     *
     * @param string $name
     * @param null $args
     * @return \aeqdev\APDO\Schema\Statement
     */
    public function __call($name, $args)
    {
        /* @var $refTable \aeqdev\APDO\Schema\Table */
        $refTable = $this->schemaTable->schema->{$name};
        if (isset($refTable)) {
            $r = $this->fetchAll();
            return $this->schemaTable->schema->{$name}()->refs($r);
        }
        return null;
    }

    protected function cacheGetRow($id, $fetchMode)
    {
        $r = parent::cacheGetRow($id, $fetchMode);
        if ($r instanceof Row) {
            $r->table = $this->schemaTable;
        }
        return $r;
    }

    protected function cacheGetStatement($statement, $args, $fetchMode)
    {
        $r = parent::cacheGetStatement($statement, $args, $fetchMode);
        if (isset($r[0]) && $r[0] instanceof Row) {
            foreach ($r as &$row) {
                $row->table = $this->schemaTable;
            }
        }
        return $r;
    }


}
