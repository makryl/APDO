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

namespace aeqdev;

/**
 *
 */
class ADBSchema extends APDO
{

    public $catalog;
    public $name;
    public $prefix;
    public $overrideMethodDocs = true;

    public function save($file, $class, $namespace = '')
    {
        $schema = $this->read();

        $f = fopen($file, 'w');
        fwrite($f, "<?php\n\n");

        if (!empty($namespace)) {
            fwrite($f, "namespace $namespace;\n\n");
            $namespace = '\\' . $namespace;
        }

        $this->renderSchema($f, $schema, $class, $namespace);

        foreach ($schema as $table => $tdata) {
            $this->renderTable($f, $table, $tdata, $class, $namespace);
            $this->renderRow($f, $table, $tdata, $namespace);
        }

        fclose($f);
    }

    protected function renderSchema($f, $schema, $class, $namespace)
    {
        fwrite($f, "class $class extends \\aeqdev\\ADBSchema\n{\n");
        if (!empty($this->catalog)) {
            fwrite($f, "    public \$catalog = '{$this->catalog}';\n");
        }
        if (!empty($this->name)) {
            fwrite($f, "    public \$name = '{$this->name}';\n");
        }
        if (!empty($this->prefix)) {
            fwrite($f, "    public \$prefix = '{$this->prefix}';\n");
        }
        if (!empty($schema)) {
            fwrite($f, "\n");
            foreach ($schema as $table => $tdata) {
                fwrite($f, "    /** @return $namespace\\Table_{$table} */\n");
                fwrite($f, "    public function {$table}() { return new Table_{$table}(\$this->parameters); }\n");
            }
        }
        fwrite($f, "}\n\n");
    }

    protected function renderTable($f, $table, $tdata, $class, $namespace)
    {
        fwrite($f, "/**\n");
        fwrite($f, " * @property $namespace\\$class \$schema\n");
        fwrite($f, " *\n");
        fwrite($f, " * @method $namespace\\Row_{$table}[] fetchAll\n");
        fwrite($f, " * @method $namespace\\Row_{$table} fetchOne\n");
        fwrite($f, " *\n");
        if ($this->overrideMethodDocs) {
            foreach ([
                'log',
                'cache',
                'nothing',
                'table',
                'pkey',
                'fetchMode',
                'join',
                'leftJoin',
                'where',
                'orWhere',
                'key',
                'orKey',
                'groupBy',
                'having',
                'orderBy',
                'addOrderBy',
                'limit',
                'offset',
                'fields',
                'handler',
                'referrers',
                'references',
            ] as $method) {
                fwrite($f, " * @method $namespace\\Table_{$table} $method\n");
            }
        }
        fwrite($f, " */\n");
        fwrite($f, "class Table_{$table} extends \\aeqdev\\ADBSchemaTable\n");
        fwrite($f, "{\n");
        fwrite($f, "    public \$name = '$table';\n");
        if (!empty($tdata['pkey'])) {
            if (count($tdata['pkey']) == 1) {
                fwrite($f, "    public \$pkey = '{$tdata['pkey'][0]}';\n");
            } else {
                fwrite($f, "    public \$pkey = ['" . implode("', '", $tdata['pkey']) . "'];\n");
            }
        }
        if (!empty($tdata['fkey'])) {
            fwrite($f, "    public \$fkey = [\n");
            foreach ($tdata['fkey'] as $rtable => $fkey) {
                fwrite($f, "        '$rtable' => '$fkey',\n");
            }
            fwrite($f, "    ];\n");
        }
        fwrite($f, "}\n\n");
    }

    protected function renderRow($f, $table, $tdata, $namespace)
    {
        fwrite($f, "/**\n");
        fwrite($f, " * @property $namespace\\Table_{$table} \$table\n");
        fwrite($f, " */\n");
        fwrite($f, "class Row_{$table} extends \\aeqdev\\ADBSchemaRow\n");
        fwrite($f, "{\n");
        if (!empty($tdata['cols'])) {
            foreach ($tdata['cols'] as $col) {
                fwrite($f, "    public \${$col};\n");
            }
        }
        if (!empty($tdata['fkey'])) {
            fwrite($f, "\n");
            foreach ($tdata['fkey'] as $rtable => $fkey) {
                fwrite($f, "    /** @var $namespace\\Row_{$rtable} */\n");
                fwrite($f, "    public \${$rtable};\n");
                fwrite($f, "    /** @return $namespace\\Row_{$rtable} */\n");
                fwrite($f, "    public function {$rtable}() { return \$this->table->schema->{$rtable}()->referrers(\$this)->fetchOne(); }\n");
            }
        }
        if (!empty($tdata['refs'])) {
            fwrite($f, "\n");
            foreach ($tdata['refs'] as $rtable) {
                fwrite($f, "    /** @var $namespace\\Row_{$rtable}[] */\n");
                fwrite($f, "    public \${$rtable} = [];\n");
                fwrite($f, "    /** @return $namespace\\Row_{$rtable}[] */\n");
                fwrite($f, "    public function {$rtable}() { return \$this->table->schema->{$rtable}()->references(\$this)->fetchAll(); }\n");
            }
        }
        fwrite($f, "}\n\n");
    }

    protected function read()
    {
        $r = $this
            ->statement($this->readQuery())
            ->fetchAll();

        $schema = [];
        $prefixLength = strlen($this->prefix);
        foreach ($r as $row) {
            $tname = substr($row->t, $prefixLength);
            $rname = substr($row->r, $prefixLength);

            $table =& $schema[$tname];
            $table['cols'][$row->col] = $row->col;
            if ($row->con == 'PRIMARY KEY') {
                $table['pkey'] []= $row->col;
            } else if ($row->con == 'FOREIGN KEY') {
                $table['fkey'][$rname] = $row->col;
                $schema[$rname]['refs'][$tname] = $tname;
            }
        }

        return $schema;
    }

    protected function readQuery()
    {
        $condition = '1=1';

        if (!empty($this->catalog)) {
            $condition .= " AND c.table_catalog = '{$this->catalog}'";
        }
        if (!empty($this->name)) {
            $condition .= " AND c.table_schema = '{$this->name}'";
        }
        if (!empty($this->prefix)) {
            $condition .= " AND c.table_name LIKE '{$this->prefix}%'";
        }

        switch (strtolower(substr($this->dsn, 0, strpos($this->dsn, ':')))) {
            case 'mysql':
                return "
SELECT
    c.table_catalog             as cat,
    c.table_schema              as s,
    c.table_name                as t,
    c.column_name               as col,
    tc.constraint_type          as con,
    rc.referenced_table_name    as r

FROM information_schema.columns c

LEFT JOIN information_schema.key_column_usage kcu
    ON  kcu.table_catalog       = c.table_catalog
    AND kcu.table_schema        = c.table_schema
    AND kcu.table_name          = c.table_name
    AND kcu.column_name         = c.column_name

LEFT JOIN information_schema.table_constraints tc
    ON  tc.constraint_catalog   = kcu.table_catalog
    AND tc.table_schema         = kcu.table_schema
    AND tc.table_name           = kcu.table_name
    AND tc.constraint_name      = kcu.constraint_name
    AND tc.constraint_type      IN ('PRIMARY KEY', 'FOREIGN KEY')

LEFT JOIN information_schema.referential_constraints rc
    ON  rc.constraint_catalog   = kcu.constraint_catalog
    AND rc.constraint_schema    = kcu.constraint_schema
    AND rc.constraint_name      = kcu.constraint_name

WHERE $condition

ORDER BY
    c.table_catalog,
    c.table_schema,
    c.table_name,
    kcu.ordinal_position,
    c.ordinal_position
";

            default:
                return "
SELECT
    c.table_catalog             as cat,
    c.table_schema              as s,
    c.table_name                as t,
    c.column_name               as col,
    tc.constraint_type          as con,
    kcu_fkey.table_name         as r

FROM information_schema.columns c

LEFT JOIN information_schema.key_column_usage kcu
    ON  kcu.table_catalog       = c.table_catalog
    AND kcu.table_schema        = c.table_schema
    AND kcu.table_name          = c.table_name
    AND kcu.column_name         = c.column_name

LEFT JOIN information_schema.table_constraints tc
    ON  tc.constraint_catalog   = kcu.table_catalog
    AND tc.table_schema         = kcu.table_schema
    AND tc.table_name           = kcu.table_name
    AND tc.constraint_name      = kcu.constraint_name
    AND tc.constraint_type      IN ('PRIMARY KEY', 'FOREIGN KEY')

LEFT JOIN information_schema.referential_constraints rc
    ON  rc.constraint_catalog   = kcu.constraint_catalog
    AND rc.constraint_schema    = kcu.constraint_schema
    AND rc.constraint_name      = kcu.constraint_name

LEFT JOIN information_schema.key_column_usage kcu_fkey
    ON  rc.unique_constraint_catalog = kcu_fkey.constraint_catalog
    AND rc.unique_constraint_schema  = kcu_fkey.constraint_schema
    AND rc.unique_constraint_name    = kcu_fkey.constraint_name

WHERE $condition

ORDER BY
    c.table_catalog,
    c.table_schema,
    c.table_name,
    kcu.ordinal_position,
    c.ordinal_position
";
        }
    }

}

/**
 *
 */
class ADBSchemaTable extends APDOStatement
{

    /**
     * @var \aeqdev\ADBSchema
     */
    public $schema;

    public $name;
    public $pkey;
    public $fkey;

    public function __construct(APDOParameters $params, $statement = null, $args = null)
    {
        parent::__construct($params, $statement, $args);

        $this->schema = $params->apdo;

        $this
            ->table($this->schema->prefix . $this->name)
            ->pkey($this->pkey)
            ->fetchMode(
                \PDO::FETCH_CLASS,
                str_replace('\\Table_', '\\Row_', get_class($this)),
                [$this]
            );
    }

    /**
     * @param array         $data           Data.
     * @param string        $referrer       Name of references in result array
     * @param string        $reference      Name of references in data array.
     * @param string        $key            Key name, that used to extract values for condition.
     *                                      By default is equal to $reference.
     * @param string        $pkey           Sets primary key to the statement. Will be used in condition.
     * @return \static                      Current statement.
     */
    public function referrers(&$data, $referrer = null, $reference = null, $key = null, $pkey = null)
    {
        if (empty($data)) {
            return $this->nothing();
        }

        /* @var $itemTable \aeqdev\ADBSchemaTable */
        $itemTable = is_array($data)
            ? reset($data)->table
            : $data->table;

        return parent::referrers(
            $data,
            $referrer   ? : $itemTable->name,
            $reference  ? : $this->name,
            $key        ? : $itemTable->fkey[$this->name],
            $pkey       ? : $itemTable->pkey
        );
    }

    /**
     * @param array         $data           Data.
     * @param string        $referrer       Name of references in data array
     * @param string        $reference      Name of references in result array.
     * @param string        $key            Key name, that used in condition.
     *                                      By default is equal to $reference.
     * @param string        $pkey           Primary key, that used to extract values for condition.
     * @return \static                      Current statement.
     */
    public function references(&$data, $referrer = null, $reference = null, $key = null, $pkey = null)
    {
        if (empty($data)) {
            return $this->nothing();
        }

        /* @var $itemTable \aeqdev\ADBSchemaTable */
        $itemTable = is_array($data)
            ? reset($data)->table
            : $data->table;

        return parent::references(
            $data,
            $referrer   ? : $this->name,
            $reference  ? : $itemTable->name,
            $key        ? : $this->fkey[$itemTable->name],
            $pkey       ? : $itemTable->pkey
        );
    }

}

/**
 *
 */
class ADBSchemaRow
{

    /**
     * @var \aeqdev\ADBSchemaTable
     */
    public $table;

    function __construct(ADBSchemaTable $table)
    {
        $this->table = $table;
    }

}
