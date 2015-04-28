<?php

namespace aeqdev\APDO\Schema;

/**
 * Imports schema from SQL string or file.
 */
class Importer
{

    public $prefix = '';
    public $suffix = '';
    public $classSchema = __NAMESPACE__;
    public $classTable  = Table::class;
    public $classRow    = Row::class;
    public $classColumn = Column::class;
    public $classColumnByType = [
        'int'    => Column\Int::class,
        'float'  => Column\Float::class,
        'bool'   => Column\Bool::class,
        'string' => Column\String::class,
        'text'   => Column\Text::class,
        'time'   => Column\Time::class,
        'date'   => Column\Date::class,
    ];

    public $colBlacklist = [
        'table',
        'values',
        '_new',
    ];

    public $fkeyBlacklist = [];

    protected $baseDir;
    protected $generatedDir;
    protected $file;
    protected $schema;
    protected $class;
    protected $namespacedClass;
    protected $namespaceGenerated;
    protected $namespace;

    function __construct()
    {
        $this->fkeyBlacklist = $this->fkeyBlacklist
            + get_class_methods(Statement::class)
            + get_class_methods(Result::class);
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
     * @param string $class Schema class name (with namespace if needed).
     * @param string $baseDir Namespace root directory.
     */
    public function save($class, $baseDir = '.')
    {
        $class = ltrim($class, '\\');
        $nssep = strrpos($class, '\\');
        if ($nssep !== false) {
            $this->namespace = substr($class, 0, $nssep);
            $this->class = substr($class, $nssep + 1);
            $this->namespacedClass = $this->namespace . '\\' . $this->class;
        } else {
            $this->namespace = '';
            $this->class = $class;
            $this->namespacedClass = $this->class;
        }
        $this->namespaceGenerated = $this->namespacedClass . '\\generated';

        $this->baseDir = $baseDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $this->namespace);
        $this->generatedDir = $baseDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $this->namespaceGenerated);
        if (!file_exists($this->generatedDir)) {
            mkdir($this->generatedDir, 0755, true);
        }

        $this->renderSchema();

        foreach ($this->schema as $table => $tdata) {
            $this->renderTable($table, $tdata);
            $this->renderRow($table, $tdata);
            $this->renderStatement($table, $tdata);
            $this->renderResult($table, $tdata);
        }
    }

    protected function getClassColumnByType($type)
    {
        return '\\' . (isset($this->classColumnByType[$type]) ? $this->classColumnByType[$type] : $this->classColumn);
    }

    protected function renderSchema()
    {
        $data = "<?php\n\nnamespace {$this->namespaceGenerated};\n";
        $data .= "\n/**\n";
        if (!empty($this->schema)) {
            foreach ($this->schema as $table => $tdata) {
                $data .= " * @property \\{$this->namespacedClass}\\Table{$this->suffix}_{$table} \${$table}{$tdata['comment']}\n";
            }
            $data .= " *\n";
            foreach ($this->schema as $table => $tdata) {
                $data .= " * @method \\{$this->namespaceGenerated}\\Statement{$this->suffix}_{$table} {$table}{$tdata['comment']}\n";
            }
        }
        $data .= " */\n";
        $data .= "class {$this->class} extends \\{$this->classSchema}\n{\n";
        if (!empty($this->prefix)) {
            $data .= "    public \$prefix = '{$this->prefix}';\n";
        }
        if (!empty($this->schema)) {
            $data .= "\n";
            $namespace = addslashes($this->namespacedClass);
            foreach ($this->schema as $table => $tdata) {
                $data .= "    public \$class_{$table} = '\\\\{$namespace}\\\\Table{$this->suffix}_{$table}';\n";
            }
        }
        $data .= "}\n";

        file_put_contents($this->generatedDir . DIRECTORY_SEPARATOR . $this->class . '.php', $data);

        $classFile = $this->baseDir . DIRECTORY_SEPARATOR . $this->class . '.php';
        if (!file_exists($classFile)) {
            $data = "<?php\n\n";
            if (!empty($this->namespace)) {
                $data .= "namespace {$this->namespace};\n\n";
            }
            $data .= "class {$this->class} extends {$this->class}\\generated\\{$this->class}\n{\n\n}\n";
            file_put_contents($classFile, $data);
        }
    }

    protected function renderTable($table, $tdata)
    {
        $class = "Table{$this->suffix}_{$table}";

        $data = "<?php\n\nnamespace {$this->namespaceGenerated};\n";
        $data .= "\n/**\n";
        $data .= " *{$tdata['comment']}\n";
        $data .= " *\n";
        $data .= " * @property \\{$this->namespacedClass} \$schema\n";
        $data .= " *\n";
        $data .= " * @method \\{$this->namespacedClass}\\Row{$this->suffix}_{$table} create\n";
        $data .= " * @method \\{$this->namespacedClass}\\Row{$this->suffix}_{$table} get\n";
        if (!empty($tdata['cols'])) {
            $data .= " *\n";
            foreach ($tdata['cols'] as $col => $cdata) {
                $cclass = $this->getClassColumnByType($cdata['type']);
                $data .= " * @method $cclass $col{$cdata['comment']}\n";
            }
        }
        $data .= " */\n";
        $data .= "class {$class} extends \\{$this->classTable}\n";
        $data .= "{\n";
        $data .= "    public \$name = '$table';\n";
        $ecomment = addslashes(trim($tdata['comment']));
        $data .= "    public \$comment = '$ecomment';\n";
        if (!empty($tdata['pkey'])) {
            if (count($tdata['pkey']) == 1) {
                $data .= "    public \$pkey = '{$tdata['pkey'][0]}';\n";
            } else {
                $data .= "    public \$pkey = ['" . implode("', '", $tdata['pkey']) . "'];\n";
            }
        }
        if (!empty($tdata['ukey'])) {
            $data .= "    public \$ukey = [\n";
            foreach ($tdata['ukey'] as $ukey) {
                $data .= "        '{$ukey}' => '{$ukey}',\n";
            }
            $data .= "    ];\n";
        }
        if (!empty($tdata['fkey'])) {
            $data .= "    public \$fkey = [\n";
            foreach ($tdata['fkey'] as $fkey => $rtable) {
                $data .= "        '$fkey' => '$rtable',\n";
            }
            $data .= "    ];\n";
            $data .= "    public \$rkey = [\n";
            foreach ($tdata['rkey'] as $rtable => $fkeys) {
                if (count($fkeys) == 1) {
                    $fkey = reset($fkeys);
                    $data .= "        '$rtable' => '{$fkey}',\n";
                } else {
                    $data .= "        '$rtable' => [\n";
                    foreach ($fkeys as $fkey => $fkey) {
                        $data .= "            '$fkey' => '$fkey',\n";
                    }
                    $data .= "        ],\n";
                }
            }
            $data .= "    ];\n";
        }
        if (!empty($tdata['cols'])) {
            $data .= "    public \$cols = [\n";
            foreach ($tdata['cols'] as $col => $cdata) {
                $data .= "        '$col' => '$col',\n";
            }
            $data .= "    ];\n";
        }
        $data .= "\n";
        $namespace = addslashes($this->namespacedClass);
        $data .= "    public \$class_row = '\\\\{$namespace}\\\\Row{$this->suffix}_{$table}';\n";
        $data .= "\n";
        if (!empty($tdata['cols'])) {
            foreach ($tdata['cols'] as $col => $cdata) {
                $cclass = $this->getClassColumnByType($cdata['type']);
                $cdef = "(new $cclass())";

                if (isset($tdata['pkey']) && in_array($col, $tdata['pkey']) && count($tdata['pkey']) == 1) {
                    $cdef .= "->nullSkip()";
                }
                if (!empty($cdata['length'])) {
                    $cdef .= "->length({$cdata['length']})";
                }
                if (isset($tdata['fkey']) && isset($tdata['fkey'][$col])) {
                    $cdef .= "->fkey()";
                }
                if (
                    empty($cdata['null'])
                    && !empty($tdata['pkey'])
                    && (!in_array($col, $tdata['pkey']) || count($tdata['pkey']) > 1)
                ) {
                    $cdef .= "->required()";
                }
                if (!empty($cdata['comment'])) {
                    $ecomment = addslashes(trim($cdata['comment']));
                    $cdef .= "->comment('$ecomment')";
                }
                $data .= "    protected function column_{$col}() { return $cdef; }\n";
            }
        }
        $data .= "}\n";

        file_put_contents($this->generatedDir . DIRECTORY_SEPARATOR . $class . '.php', $data);

        $classFile = $this->baseDir . DIRECTORY_SEPARATOR . $this->class . DIRECTORY_SEPARATOR . $class . '.php';
        if (!file_exists($classFile)) {
            file_put_contents($classFile, "<?php\n\nnamespace {$this->namespacedClass};\n\nclass $class extends generated\\$class\n{\n\n}\n");
        }
    }

    protected function renderRow($table, $tdata)
    {
        $class = "Row{$this->suffix}_{$table}";

        $data = "<?php\n\nnamespace {$this->namespaceGenerated};\n";
        $data .= "\n/**\n";
        $data .= " *{$tdata['comment']}\n";
        $data .= " *\n";
        $data .= " * @property \\{$this->namespacedClass}\\Table{$this->suffix}_{$table} \$table\n";
        $data .= $this->renderRefsMethods($tdata, true);
        $data .= " */\n";
        $data .= "class {$class} extends \\{$this->classRow}\n";
        $data .= "{\n";
        if (!empty($tdata['cols'])) {
            foreach ($tdata['cols'] as $col => $cdata) {
                $ctype = in_array($cdata['type'], [
                    'bool',
                    'int',
                    'float',
                    'string',
                ]) ? $cdata['type'] : 'string';
                if (isset($tdata['fkey'][$col])) {
                    $data .= "    /** @var $ctype|\\{$this->namespacedClass}\\Row{$this->suffix}_{$tdata['fkey'][$col]}{$cdata['comment']} */\n";

                } else {
                    $data .= "    /** @var $ctype{$cdata['comment']} */\n";
                }
                $data .= "    public \${$col};\n";
            }
        }
        if (!empty($tdata['refs'])) {
            $data .= "\n";
            foreach ($tdata['refs'] as $rtable => $fkeys) {
                $comment = $this->schema[$rtable]['comment'];
                if (count($fkeys) == 1) {
                    $fkey = reset($fkeys);
                    if (isset($this->schema[$rtable]['ukey'][$fkey])) {
                        $data .= "    /** @var \\{$this->namespacedClass}\\Row{$this->suffix}_{$rtable}{$comment} */\n";
                        $data .= "    public \${$rtable};\n";
                    } else {
                        $data .= "    /** @var \\{$this->namespacedClass}\\Row{$this->suffix}_{$rtable}[]{$comment} */\n";
                        $data .= "    public \${$rtable} = [];\n";
                    }
                } else {
                    foreach ($fkeys as $fkey) {
                        $fcomment = $comment . ' (' . trim($this->schema[$rtable]['cols'][$fkey]['comment']) . ')';
                        if (isset($this->schema[$rtable]['ukey'][$fkey])) {
                            $data .= "    /** @var \\{$this->namespacedClass}\\Row{$this->suffix}_{$rtable}{$fcomment} */\n";
                            $data .= "    public \${$rtable}__{$fkey};\n";
                        } else {
                            $data .= "    /** @var \\{$this->namespacedClass}\\Row{$this->suffix}_{$rtable}[]{$fcomment} */\n";
                            $data .= "    public \${$rtable}__{$fkey} = [];\n";
                        }
                    }
                }
            }
        }
        $data .= "}\n";

        file_put_contents($this->generatedDir . DIRECTORY_SEPARATOR . $class . '.php', $data);

        $classFile = $this->baseDir . DIRECTORY_SEPARATOR . $this->class . DIRECTORY_SEPARATOR . $class . '.php';
        if (!file_exists($classFile)) {
            file_put_contents($classFile, "<?php\n\nnamespace {$this->namespacedClass};\n\nclass $class extends generated\\$class\n{\n\n}\n");
        }
    }

    protected function renderStatement($table, $tdata)
    {
        $class = "Statement{$this->suffix}_{$table}";

        $data = "<?php\n\nnamespace {$this->namespaceGenerated};\n";
        $data .= "\n/**\n";
        $data .= " *{$tdata['comment']}\n";
        $data .= " *\n";
        $data .= " * @method \\{$this->namespaceGenerated}\\Result{$this->suffix}_{$table} fetchAll\n";
        $data .= " * @method \\{$this->namespaceGenerated}\\Result{$this->suffix}_{$table} fetchPage\n";
        $data .= " * @method \\{$this->namespacedClass}\\Row{$this->suffix}_{$table} fetchOne\n";
        $data .= $this->renderRefsMethods($tdata);
        $data .= " */\n";
        $namespace = __NAMESPACE__;
        $data .= "class Statement{$this->suffix}_{$table} extends \\{$namespace}\\Statement {}\n";

        file_put_contents($this->generatedDir . DIRECTORY_SEPARATOR . $class . '.php', $data);
    }

    protected function renderResult($table, $tdata)
    {
        $class = "Result{$this->suffix}_{$table}";

        $data = "<?php\n\nnamespace {$this->namespaceGenerated};\n";
        $data .= "\n/**\n";
        $data .= " *{$tdata['comment']}\n";
        $data .= $this->renderRefsMethods($tdata);
        $data .= " */\n";
        $namespace = __NAMESPACE__;
        $data .= "class Result{$this->suffix}_{$table} extends \\{$namespace}\\Result {}\n";

        file_put_contents($this->generatedDir . DIRECTORY_SEPARATOR . $class . '.php', $data);
    }

    protected function renderRefsMethods($tdata, $isRow = false) {
        $data = '';
        if (!empty($tdata['fkey'])) {
            $data .= " *\n";
            foreach ($tdata['fkey'] as $fkey => $rtable) {
                $rclass = '\\' . $this->namespacedClass . '\\'
                    . ($isRow ? 'Row' : 'generated\\Statement')
                    . $this->suffix . '_' . $rtable;
                $data .= " * @method {$rclass} {$fkey}{$tdata['cols'][$fkey]['comment']}\n";
            }
        }
        if (!empty($tdata['refs'])) {
            $data .= " *\n";
            foreach ($tdata['refs'] as $rtable => $fkeys) {
                $comment = $this->schema[$rtable]['comment'];
                if (count($fkeys) == 1) {
                    $fkey = reset($fkeys);
                    $rclass = '\\' . $this->namespacedClass . '\\'
                        . ($isRow && isset($this->schema[$rtable]['ukey'][$fkey]) ? 'Row' : 'generated\\Statement')
                        . $this->suffix . '_' . $rtable;
                    $data .= " * @method {$rclass} {$rtable}{$comment}\n";
                } else {
                    foreach ($fkeys as $fkey) {
                        $fcomment = $comment . ' (' . trim($this->schema[$rtable]['cols'][$fkey]['comment']) . ')';
                        $rclass = '\\' . $this->namespacedClass . '\\'
                            . ($isRow && isset($this->schema[$rtable]['ukey'][$fkey]) ? 'Row' : 'generated\\Statement')
                            . $this->suffix . '_' . $rtable;
                        $data .= " * @method {$rclass} {$rtable}__{$fkey}{$fcomment}\n";
                    }
                }
            }
        }

        return $data;
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
        $sql = preg_replace('/([\r\n]+)\s*--.*[\r\n]+/', '$1', $sql);

        $sql = trim($sql);

        #statements
        foreach (explode(';', $sql) as $st) {

            # create table
            if (!preg_match('/'
                . 'create\s+table\s+'
                . '(if\s+not\s+exists\s+)?'
                . '(\w+)\s*'
                . '(--([^\r\n]+)[\r\n]+)?\s*'
                . '\((.*)\)'
                . '(.+comment\s+["\']([^"\']+)["\'])?'
                . '/is', $st, $m)
            ) {
                continue;
            }

            $tname = substr($m[2], $prefixLength);
            $tdef = $m[5];
            $table = [
                'comment' => (
                    isset($m[7])
                        ? ' ' . trim($m[7])
                        : (
                            isset($m[4])
                                ? ' ' . trim($m[4])
                                : ''
                        )
                    )
            ];

            # columns
            $cname = null;

            foreach (preg_split('/(,\s*|,\s*--\s*[^\r\n]*)[\r\n]+/s', $tdef, -1, PREG_SPLIT_DELIM_CAPTURE) as $def) {
                if (!empty($def) && $def[0] == ',') {
                    # comment after comma
                    if (preg_match('/^,\s*--\s*(.*)$/', $def, $m)) {
                        if (isset($cname)) {
                            $table['cols'][$cname]['comment'] = ' ' . trim($m[1]);
                        }
                    }
                }

                # primary key
                if (preg_match('/primary\s+key\s*\((.*)\)/i', $def, $m)) {
                    $table['pkey'] = preg_split('/,\s+/', $m[1]);

                # unique key
                } else if (preg_match('/unique\s+key\s*\w*\s*\((.*)\)/i', $def, $m)) {
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
                    $table['fkey'][$m[1]] = $rtable;
                    $table['rkey'][$rtable][$m[1]] = $m[1];
                    $this->schema[$rtable]['refs'][$tname][$m[1]] = $m[1];

                # key
                } else if (preg_match('/^\s*key\s+/i', $def, $m)) {
                    continue;

                # column
                } else if (preg_match('/^\s*(\w+)\s+(.*)$/i', $def, $m)) {
                    $cname = $m[1];
                    $cdef = $m[2];
                    $col = [];

                    # column type
                    if (preg_match('/(bool|char|text|blob|datetime|time|date|int|float|real|double|decimal)/i', $cdef, $m)) {
                        switch (strtolower($m[1])) {
                            case     'bool': $col['type'] = 'bool';   break;
                            case     'char': $col['type'] = 'string'; break;
                            case     'text': $col['type'] = 'text';   break;
                            case     'blob': $col['type'] = 'text';   break;
                            case 'datetime': $col['type'] = 'time';   break;
                            case     'time': $col['type'] = 'time';   break;
                            case     'date': $col['type'] = 'date';   break;
                            case      'int': $col['type'] = 'int';    break;
                            case   'serial': $col['type'] = 'int';    break;
                            case    'float': $col['type'] = 'float';  break;
                            case     'real': $col['type'] = 'float';  break;
                            case   'double': $col['type'] = 'float';  break;
                            case  'decimal': $col['type'] = 'float';  break;
                        }
                    } else {
                        $col['type'] = 'string';
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

                    # comment
                    if (
                        preg_match('/comment\s+\'([^\']+)\'/i', $cdef, $m)
                        || preg_match('/comment\s+"([^"]+)"/i', $cdef, $m)
                    ) {
                        $col['comment'] = ' ' . trim($m[1]);
                    } else {
                        $col['comment'] = '';
                    }

                    $table['cols'][$cname] = $col;
                }
            }

            $this->schema[$tname] = $table;
        }
    }

}
