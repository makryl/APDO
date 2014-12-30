<?php

namespace aeqdev\APDO\Schema;

use PDO;

/**
 * Schema statement can set schema table parameters and work with schema table references.
 */
class Statement extends \aeqdev\APDO\Statement
{

    /**
     * @var Table
     */
    protected $schemaTable;

    /**
     * Sets schema table for the statement.
     *
     * @param Table $table Schema table.
     * @return static|$this Current statement.
     */
    public function schemaTable(Table $table)
    {
        $this->schemaTable = $table;
        return $this->table($table->schema->prefix . $table->name)
            ->pkey($table->pkey)
            ->fetchMode(PDO::FETCH_CLASS, $table->class_row, [$table])
            ->handler(function($result) {
                return new Result($this->schemaTable, $result);
            });
    }

    /**
     * Tries to create appropriate refs statement, and fetch object if needed.
     *
     * Chooses appropriate Statement method
     * (one of: referrers, referrersUnique, references, referencesUnique)
     * using data passed in argument and foreign keys of tables.
     *
     * If no appropriate foreign key found, returns null.
     *
     * @param Row|Result $data Data.
     * @param string $name Reference name. Foreign key name, table name or table__fkey.
     * @return null|Row|Statement Reference statement,
     *                            or object for one to one reference,
     *                            or null if no foreign key found.
     */
    public static function refs($data, $name)
    {
        $statement = null;
        $unique = false;
        $dataFKey = false;
        $parts = explode('__', $name);
        if (isset($parts[1])) { // $tree->fruit__tree()
            list($rtname, $key) = $parts;
            $rtable = $data->table->schema->{$rtname};
            if (
                isset($rtable)
                && isset($rtable->fkey[$key])
                && $rtable->fkey[$key] == $data->table->name
                && is_array($rtable->rkey[$data->table->name])
            ) {
                $unique = isset($rtable->ukey[$key]);
                $statement = $rtable->statement()->references(
                    $data,
                    $name,
                    $key,
                    $key,
                    $data->table->pkey,
                    $unique
                );
            }
        } else if (
            isset($data->table->fkey[$name])
            && is_string($data->table->rkey[$data->table->fkey[$name]])
        ) { // $fruit->tree()
            $rtable = $data->table->schema->{$data->table->fkey[$name]};
            if (isset($rtable)) {
                $unique = isset($data->table->ukey[$name]);
                $dataFKey = true;
                $statement = $rtable->statement()->referrers(
                    $data,
                    $data->table->name,
                    $name,
                    $name,
                    $data->table->pkey,
                    $unique
                );
            }
        } else { // $tree->fruit()
            $rtable = $data->table->schema->{$name};
            if (
                isset($rtable)
                && isset($rtable->rkey[$data->table->name])
                && is_string($rtable->rkey[$data->table->name])
            ) {
                $key = $rtable->rkey[$data->table->name];
                $unique = isset($rtable->ukey[$key]);
                $statement = $rtable->statement()->references(
                    $data,
                    $name,
                    $key,
                    $key,
                    $data->table->pkey,
                    $unique
                );
            }
        }
        return ($data instanceof Row && ($unique || $dataFKey))
            ? $statement->fetchOne()
            : $statement;
    }

    /**
     * Tries to create appropriate refs statement, and fetch object if needed.
     *
     * @param string $name
     * @param null $args
     * @return null|Statement
     */
    public function __call($name, $args)
    {
        return $this->fetchAll()->{$name}();
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
