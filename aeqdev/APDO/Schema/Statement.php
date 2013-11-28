<?php

/*
 * http://aeqdev.com/tools/php/apdo/
 * v 0.1
 *
 * Copyright © 2013 Krylosov Maksim <Aequiternus@gmail.com>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

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
            ->pkey($table->pkey)
            ->fetchMode(\PDO::FETCH_CLASS, $table->class_row, [$table]);
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
