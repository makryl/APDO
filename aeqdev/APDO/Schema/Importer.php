<?php

/*
 * http://aeqdev.com/tools/php/apdo/
 * v 0.2
 *
 * Copyright © 2013 Krylosov Maksim <Aequiternus@gmail.com>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace aeqdev\APDO\Schema;

/**
 * Imports schema from SQL string or file.
 */
class Importer
{

    public $prefix = '';
    public $overrideStatementDocs = true;
    public $uses;
    public $classSchema;
    public $classTable;
    public $classRow;
    public $classColumn;
    public $classColumnByType;

    protected $file;
    protected $schema;
    protected $class;
    protected $namespace;

    function __construct()
    {
        $this->classSchema = '\\' . __NAMESPACE__;
        $this->classTable  = '\\' . __NAMESPACE__ . '\\Table';
        $this->classRow    = '\\' . __NAMESPACE__ . '\\Row';
        $this->classColumn = '\\' . __NAMESPACE__ . '\\Column';

        $this->classColumnByType = [
            'int'    => $this->classColumn . '\\Int',
            'float'  => $this->classColumn . '\\Float',
            'bool'   => $this->classColumn . '\\Bool',
            'string' => $this->classColumn . '\\String',
            'text'   => $this->classColumn . '\\Text',
            'time'   => $this->classColumn . '\\Time',
            'date'   => $this->classColumn . '\\Date',
        ];
    }

    /**
     * @return array Schema in internal format.
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Reads SQL file.
     *
     * @param string $file File name to read.
     */
    public function read($file)
    {
        $this->readSQL(file_get_contents($file));
    }

    /**
     * Saves schema into file.
     *
     * @param string $file File name to write.
     * @param string $class Schema class name (with namespace if needed).
     */
    public function save($file, $class)
    {
        $this->file = fopen($file, 'w');

        $class = ltrim($class, '\\');
        $nssep = strrpos($class, '\\');
        if ($nssep !== false) {
            $this->namespace = substr($class, 0, $nssep);
            $class = substr($class, $nssep + 1);
        } else {
            $this->namespace = '';
        }
        $this->class = $class;

        fwrite($this->file, "<?php\n\n");

        if (!empty($this->namespace)) {
            fwrite($this->file, "namespace {$this->namespace};\n\n");
        }

        if (!empty($this->uses)) {
            foreach ($this->uses as $use) {
                fwrite($this->file, "use $use;\n");
            }
            fwrite($this->file, "\n");
        }

        $this->renderSchema();

        foreach ($this->schema as $table => $tdata) {
            $this->renderTable($table, $tdata);
            $this->renderRow($table, $tdata);
        }

        foreach ($this->schema as $table => $tdata) {
            $this->renderStatement($table, $tdata);
        }

        fclose($this->file);
    }

    protected function getClassColumnByType($type)
    {
        return isset($this->classColumnByType[$type]) ? $this->classColumnByType[$type] : $this->classColumn;
    }

    protected function renderSchema()
    {
        fwrite($this->file, "/**\n");
        if (!empty($this->schema)) {
            foreach ($this->schema as $table => $tdata) {
                fwrite($this->file, " * @property \\{$this->namespace}\\Table_{$table} \${$table}\n");
            }
            fwrite($this->file, " *\n");
            foreach ($this->schema as $table => $tdata) {
                fwrite($this->file, " * @method \\{$this->namespace}\\Statement_{$table} {$table}\n");
            }
        }
        fwrite($this->file, " */\n");
        fwrite($this->file, "class {$this->class} extends {$this->classSchema}\n{\n");
        if (!empty($this->prefix)) {
            fwrite($this->file, "    public \$prefix = '{$this->prefix}';\n");
        }
        if (!empty($this->schema)) {
            fwrite($this->file, "\n");
            foreach ($this->schema as $table => $tdata) {
                fwrite($this->file, "    public \$class_{$table} = '\\\\{$this->namespace}\\\\Table_{$table}';\n");
            }
        }
        fwrite($this->file, "}\n\n");
    }

    protected function renderTable($table, $tdata)
    {
        fwrite($this->file, "/**\n");
        fwrite($this->file, " * @property \\{$this->namespace}\\{$this->class} \$schema\n");
        fwrite($this->file, " *\n");
        fwrite($this->file, " * @method \\{$this->namespace}\\Row_{$table} create\n");
        fwrite($this->file, " * @method \\{$this->namespace}\\Row_{$table} get\n");
        if (!empty($tdata['cols'])) {
            fwrite($this->file, " *\n");
            foreach ($tdata['cols'] as $col => $cdata) {
                $cclass = $this->getClassColumnByType($cdata['type']);
                fwrite($this->file, " * @method $cclass $col\n");
            }
        }
        fwrite($this->file, " */\n");
        fwrite($this->file, "class Table_{$table} extends {$this->classTable}\n");
        fwrite($this->file, "{\n");
        fwrite($this->file, "    public \$name = '$table';\n");
        if (!empty($tdata['pkey'])) {
            if (count($tdata['pkey']) == 1) {
                fwrite($this->file, "    public \$pkey = '{$tdata['pkey'][0]}';\n");
            } else {
                fwrite($this->file, "    public \$pkey = ['" . implode("', '", $tdata['pkey']) . "'];\n");
            }
        }
        if (!empty($tdata['ukey'])) {
            fwrite($this->file, "    public \$ukey = [\n");
            foreach ($tdata['ukey'] as $ukey) {
                fwrite($this->file, "        '{$ukey}' => '{$ukey}',\n");
            }
            fwrite($this->file, "    ];\n");
        }
        if (!empty($tdata['fkey'])) {
            fwrite($this->file, "    public \$fkey = [\n");
            foreach ($tdata['fkey'] as $rtable => $fkey) {
                fwrite($this->file, "        '$rtable' => '$fkey',\n");
            }
            fwrite($this->file, "    ];\n");
            fwrite($this->file, "    public \$rtable = [\n");
            foreach ($tdata['fkey'] as $rtable => $fkey) {
                fwrite($this->file, "        '$fkey' => '$rtable',\n");
            }
            fwrite($this->file, "    ];\n");
        }
        if (!empty($tdata['cols'])) {
            fwrite($this->file, "    public \$cols = [\n");
            foreach ($tdata['cols'] as $col => $cdata) {
                fwrite($this->file, "        '$col' => '$col',\n");
            }
            fwrite($this->file, "    ];\n");
        }
        fwrite($this->file, "\n");
        fwrite($this->file, "    public \$class_row = '\\\\{$this->namespace}\\\\Row_{$table}';\n");
        fwrite($this->file, "\n");
        if (!empty($tdata['cols'])) {
            foreach ($tdata['cols'] as $col => $cdata) {
                $cclass = $this->getClassColumnByType($cdata['type']);
                $cdef = "(new $cclass())";

                if (!empty($cdata['length'])) {
                    $cdef = $cdef . "->length({$cdata['length']})";
                }
                if (!empty($tdata['fkey'])) {
                    $rtable = array_search($col, $tdata['fkey']);
                    if ($rtable !== false) {
                        $cdef = $cdef . "->fkey()";
                    }
                }
                if (empty($cdata['null']) && !in_array($col, $tdata['pkey'])) {
                    $cdef = $cdef . "->required()";
                }
                fwrite($this->file, "    protected function column_{$col}() { return $cdef; }\n");
            }
        }
        fwrite($this->file, "}\n\n");
    }

    protected function renderRow($table, $tdata)
    {
        fwrite($this->file, "/**\n");
        fwrite($this->file, " * @property \\{$this->namespace}\\Table_{$table} \$table\n");
        if (!empty($tdata['fkey'])) {
            fwrite($this->file, " *\n");
            foreach ($tdata['fkey'] as $rtable => $fkey) {
                fwrite($this->file, " * @method \\{$this->namespace}\\Row_{$rtable} {$rtable}\n");
            }
        }
        if (!empty($tdata['refs'])) {
            fwrite($this->file, " *\n");
            foreach ($tdata['refs'] as $rtable) {
                if (isset($this->schema[$rtable]['ukey'][$this->schema[$rtable]['fkey'][$table]])) {
                    fwrite($this->file, " * @method \\{$this->namespace}\\Row_{$rtable} {$rtable}\n");
                } else {
                    fwrite($this->file, " * @method \\{$this->namespace}\\Row_{$rtable}[] {$rtable}\n");
                }
            }
        }
        fwrite($this->file, " */\n");
        fwrite($this->file, "class Row_{$table} extends {$this->classRow}\n");
        fwrite($this->file, "{\n");
        if (!empty($tdata['cols'])) {
            foreach ($tdata['cols'] as $col => $cdata) {
                fwrite($this->file, "    public \${$col};\n");
            }
        }
        if (!empty($tdata['fkey'])) {
            fwrite($this->file, "\n");
            foreach ($tdata['fkey'] as $rtable => $fkey) {
                fwrite($this->file, "    /** @var \\{$this->namespace}\\Row_{$rtable} */\n");
                fwrite($this->file, "    public \${$rtable};\n");
            }
        }
        if (!empty($tdata['refs'])) {
            fwrite($this->file, "\n");
            foreach ($tdata['refs'] as $rtable) {
                if (isset($this->schema[$rtable]['ukey'][$this->schema[$rtable]['fkey'][$table]])) {
                    fwrite($this->file, "    /** @var \\{$this->namespace}\\Row_{$rtable} */\n");
                    fwrite($this->file, "    public \${$rtable};\n");
                } else {
                    fwrite($this->file, "    /** @var \\{$this->namespace}\\Row_{$rtable}[] */\n");
                    fwrite($this->file, "    public \${$rtable} = [];\n");
                }
            }
        }
        fwrite($this->file, "}\n\n");
    }

    protected function renderStatement($table, $tdata)
    {
        fwrite($this->file, "/**\n");
        fwrite($this->file, " * @method \\{$this->namespace}\\Row_{$table}[] fetchAll\n");
        fwrite($this->file, " * @method \\{$this->namespace}\\Row_{$table}[] fetchPage\n");
        fwrite($this->file, " * @method \\{$this->namespace}\\Row_{$table} fetchOne\n");
        fwrite($this->file, " *\n");
        if ($this->overrideStatementDocs) {
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
                'referrersUnique',
                'referencesUnique',
                'refs',
            ] as $method) {
                fwrite($this->file, " * @method \\{$this->namespace}\\Statement_{$table} $method\n");
            }
        }
        if (!empty($tdata['fkey'])) {
            fwrite($this->file, " *\n");
            foreach ($tdata['fkey'] as $rtable => $fkey) {
                fwrite($this->file, " * @method \\{$this->namespace}\\Statement_{$rtable} {$rtable}\n");
            }
        }
        if (!empty($tdata['refs'])) {
            fwrite($this->file, " *\n");
            foreach ($tdata['refs'] as $rtable) {
                if (isset($this->schema[$rtable]['ukey'][$this->schema[$rtable]['fkey'][$table]])) {
                    fwrite($this->file, " * @method \\{$this->namespace}\\Statement_{$rtable} {$rtable}\n");
                } else {
                    fwrite($this->file, " * @method \\{$this->namespace}\\Statement_{$rtable}[] {$rtable}\n");
                }
            }
        }
        fwrite($this->file, " */\n");
        $namespace = __NAMESPACE__;
        fwrite($this->file, "class Statement_{$table} extends \\{$namespace}\\Statement {}\n\n");
    }

    /**
     * Reads schema from SQL string.
     *
     * @param string $sql String of SQL statements to read.
     */
    public function readSQL($sql)
    {
        $this->schema = [];
        $prefixLength = strlen($this->prefix);

        # escaping
        $sql = str_replace(['`', '"'], '', $sql);

        # comments
        $sql = preg_replace('/--.*$/', '', $sql);

        $sql = trim($sql);

        #statements
        foreach (explode(';', $sql) as $st) {

            # create table
            if (!preg_match('/create\s+table\s+(\w+)\s*\((.*)\)/is', $st, $m)) {
                continue;
            }

            $tname = substr($m[1], $prefixLength);
            $tdef = $m[2];
            $table = [];

            # columns
            foreach (explode(',', $tdef) as $def) {

                # primary key
                if (preg_match('/primary\s+key\s*\((.*)\)/i', $def, $m)) {
                    $table['pkey'] = preg_split('/,\s+/', $m[1]);

                # unique key
                } else if (preg_match('/unique\s+key\s*\((.*)\)/i', $def, $m)) {
                    if (strpos($m[1], ',') !== false) {
                        continue;
                    }
                    $table['ukey'][$m[1]] = $m[1];

                # foreign key
                } else if (preg_match('/foreign\s+key\s*\w*\s*\((.*)\)\s+references\s+(\w+)\s*\((.*)\)/i', $def, $m)) {
                    if (strpos($m[1], ',') !== false) {
                        continue;
                    }
                    $rtable = substr($m[2], $prefixLength);
                    $table['fkey'][$rtable] = $m[1];
                    $this->schema[$rtable]['refs'][$tname] = $tname;

                # column
                } else if (preg_match('/^\s*(\w+)\s+(.*)$/i', $def, $m)) {
                    $cname = $m[1];
                    $cdef = $m[2];
                    $col = [];

                    # column type
                    if (preg_match('/(bool|char|text|blob|time|date|int|float|real|double|decimal)/i', $cdef, $m)) {
                        switch (strtolower($m[1])) {
                            case    'bool': $col['type'] = 'bool';   break;
                            case    'char': $col['type'] = 'string'; break;
                            case    'text': $col['type'] = 'text';   break;
                            case    'blob': $col['type'] = 'text';   break;
                            case    'time': $col['type'] = 'time';   break;
                            case    'date': $col['type'] = 'date';   break;
                            case     'int': $col['type'] = 'int';    break;
                            case  'serial': $col['type'] = 'int';    break;
                            case   'float': $col['type'] = 'float';  break;
                            case    'real': $col['type'] = 'float';  break;
                            case  'double': $col['type'] = 'float';  break;
                            case 'decimal': $col['type'] = 'float';  break;
                        }
                    }

                    # length
                    if ($col['type'] == 'string' || $col['type'] == 'text') {
                        if (preg_match('/\(\s*(\d+)\s*\)/', $cdef, $m)) {
                            $col['length'] = $m[1];
                        }
                    }

                    # null
                    $col['null'] = (stripos($cdef, 'not null') === false);

                    # primary key
                    if (preg_match('/primary\s+key/i', $cdef)) {
                        $table['pkey'] = [$cname];
                    }

                    # unique key
                    if (stripos($cdef, 'unique') !== false) {
                        $table['ukey'][$cname] = $cname;
                    }

                    # foreign key
                    if (preg_match('/references\s+(\w+)\s*\((.*)\)/i', $cdef, $m)) {
                        $rtable = substr($m[1], $prefixLength);
                        $table['fkey'][$rtable] = $cname;
                        $this->schema[$rtable]['refs'][$tname] = $tname;
                    }

                    $table['cols'][$cname] = $col;
                }
            }

            $this->schema[$tname] = $table;
        }
    }

}
