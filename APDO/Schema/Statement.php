<?php

namespace aeqdev\APDO\Schema;

/**
 *
 */
class Statement extends \aeqdev\APDOStatement
{

    protected $schemaTable;

    public function schemaTable(Table $table)
    {
        $this->schemaTable = $table;
        return $this->table($table->schema->prefix . $table->name)
            ->pkey($table->pkey);
    }

    public function refs(&$data)
    {
        if (empty($data)) {
            return $this->nothing();
        }

        $itemTable = is_array($data)
            ? reset($data)->table()
            : $data->table();

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
        }
    }

}
