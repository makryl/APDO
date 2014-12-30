<?php

namespace aeqdev\APDO\Schema;

use \aeqdev\APDO\Schema;

/**
 * Exports schema (full or diff) to SQL.
 */
class Exporter
{

    public $pkeyAutoIncrement = false;
    public $pkeyTypeSerial = false;

    /**
     * @var Schema
     */
    public $readSchema;
    public $typeByClassColumn;
    public $sqlTypes = [
        'int'    => 'integer',
        'float'  => 'float',
        'bool'   => 'boolean',
        'string' => 'varchar',
        'text'   => 'text',
        'time'   => 'timestamp',
        'date'   => 'date',
    ];

    protected $sql;
    protected $schema;
    protected $compare;

    public function __construct()
    {
        $ns = __NAMESPACE__ . '\\Column\\';
        $this->typeByClassColumn = [
            $ns . 'Int'    => 'int',
            $ns . 'Float'  => 'float',
            $ns . 'Bool'   => 'bool',
            $ns . 'String' => 'string',
            $ns . 'Text'   => 'text',
            $ns . 'Time'   => 'time',
            $ns . 'Date'   => 'date',
        ];
    }

    /**
     * @return array Schema in internal format.
     */
    public function getSchema()
    {
        return $this->schema;
    }

    public function execute($withDrops = false)
    {
        $sql = $this->getSQL($withDrops);

        # comments
        $sql = preg_replace('/--.*$/', '', $sql);

        $sql = trim($sql);

        foreach (explode(';', $sql) as $statement) {
            $this->readSchema->statement($statement)->execute();
        }
    }

    public function save($file)
    {
        file_put_contents($file, $this->getSQL());
    }

    public function compareWithSQL($sql)
    {
        $importer = new Importer();
        $importer->prefix = $this->readSchema->prefix;
        $importer->readSQL($sql);
        $this->compare = $importer->getSchema();
    }

    public function compareWithSQLFile($file)
    {
        $importer = new Importer();
        $importer->prefix = $this->readSchema->prefix;
        $importer->read($file);
        $this->compare = $importer->getSchema();
    }

    public function readSchema(Schema $schema)
    {
        $this->readSchema = $schema;

        foreach ($schema as $property => $tclass) {
            if (substr($property, 0, 6) != 'class_') {
                continue;
            }

            $tname = substr($property, 6);
            $table = $schema->{$tname};
            $pkey = (array)$table->pkey;

            $this->schema[$tname]['comment'] = empty($table->comment) ? '' : ' ' . $table->comment;

            if (!empty($table->cols)) {
                foreach ($table->cols as $cname) {
                    /** @var Column $col */
                    $col = $table->{$cname}();

                    $this->schema[$tname]['cols'][$cname]['type'] = $this->typeByClassColumn[get_class($col)];

                    if (!empty($col->length)) {
                        $this->schema[$tname]['cols'][$cname]['length'] = $col->length;
                    }

                    $this->schema[$tname]['cols'][$cname]['null']
                        = in_array($cname, $pkey)
                        ? false
                        : (isset($col->null) ? $col->null : true);

                    $this->schema[$tname]['cols'][$cname]['comment'] = empty($col->comment) ? '' : ' ' . $col->comment;
                }
            }

            foreach ($pkey as $pk) {
                $this->schema[$tname]['pkey'] [] = $pk;
            }

            if (!empty($table->ukey)) {
                $this->schema[$tname]['ukey'] = $table->ukey;
            }

            if (!empty($table->fkey)) {
                $this->schema[$tname]['fkey'] = $table->fkey;
                foreach ($table->fkey as $fkey => $rtable) {
                    $this->schema[$rtable]['refs'][$tname][$fkey] = $fkey;
                    $this->schema[$tname]['rkey'][$rtable][$fkey] = $fkey;
                }
            }
        }
    }

    public function getSQL($withDrops = false)
    {
        if (isset($this->compare)) {
            return $this->getDiffSQL($withDrops);
        } else {
            return $this->getFullSQL();
        }
    }

    public function getFullSQL()
    {
        $this->sql = '';
        $prefix = $this->readSchema->prefix;

        foreach ($this->schema as $tname => $tdata) {
            $this->renderTable($prefix, $tname, $tdata);
        }

        return $this->sql;
    }

    protected function renderTable($tablePrefix, $tname, $tdata)
    {
        $this->sql .= "\nCREATE TABLE {$tablePrefix}$tname (\n";
        $prefix = "    ";
        $suffix = ",\n";

        if (!empty($tdata['cols'])) {
            foreach ($tdata['cols'] as $cname => $cdata) {
                $this->renderCol($cname, $cdata, $tdata, $prefix, $suffix);
            }
        }

        $this->sql .= "\n";

        if (!empty($tdata['fkey'])) {
            foreach ($tdata['fkey'] as $fkey => $rtable) {
                $this->renderFkey($tablePrefix, $fkey, $rtable, $prefix, $suffix);
            }
        }

        if (!empty($tdata['ukey'])) {
            foreach ($tdata['ukey'] as $ukey) {
                $this->renderUkey($ukey, $prefix, $suffix);
            }
        }

        $this->renderPkey($tdata, $prefix, "\n");

        $ecomment = addslashes(trim($tdata['comment']));
        $this->sql .= ") COMMENT '{$ecomment}';\n";
    }

    protected function renderCol($cname, $cdata, $tdata, $prefix, $suffix)
    {
        $type = $this->sqlTypes[$cdata['type']];
        $length = empty($cdata['length']) ? '' : '(' . $cdata['length'] . ')';
        $null = empty($cdata['null']) ? ' NOT NULL' : '';

        if (in_array($cname, $tdata['pkey'])) {
            if ($this->pkeyAutoIncrement) {
                $suffix = ' AUTO_INCREMENT' . $suffix;
            }
            if ($this->pkeyTypeSerial) {
                $type = 'serial';
            }
        }

        $comment = empty($cdata['comment']) ? '' : " COMMENT '" . addslashes(trim($cdata['comment'])) ."'";

        $this->sql .= $prefix . $cname . ' ' . $type . $length . $null . $comment . $suffix;
    }

    protected function renderFkey($tablePrefix, $fkey, $rtable, $prefix, $suffix)
    {
        $rid = $this->schema[$rtable]['pkey'][0];
        $this->sql .= $prefix . 'FOREIGN KEY (' . $fkey . ') REFERENCES '
            . $tablePrefix . $rtable . '(' . $rid . ')' . $suffix;
    }

    protected function renderUkey($ukey, $prefix, $suffix)
    {
        $this->sql .= $prefix . 'UNIQUE KEY (' . $ukey . ')' . $suffix;
    }

    protected function renderPkey($tdata, $prefix, $suffix)
    {
        $pkey = implode(', ', $tdata['pkey']);
        $this->sql .= $prefix . 'PRIMARY KEY (' . $pkey . ')' . $suffix;
    }

    public function getDiffSQL($withDrops = false)
    {
        $this->sql = '';
        $tablePrefix = $this->readSchema->prefix;
        $cmt = "\n" . ($withDrops ? '' : '-- ');
        $suffix = ";\n";

        foreach ($this->compare as $tname => $tdata) {
            if (isset($this->schema[$tname])) {
                $ctdata = $this->schema[$tname];
                $prefix = "{$cmt}ALTER TABLE {$tablePrefix}$tname DROP ";

                if (!empty($tdata['cols'])) {
                    foreach ($tdata['cols'] as $cname => $cdata) {
                        if (!isset($ctdata['cols'][$cname])) {
                            $this->sql .= $prefix . $cname . $suffix;
                        }
                    }
                }

//                if (!empty($tdata['fkey'])) {
//                    foreach ($tdata['fkey'] as $fkey => $rtable) {
//                        if (!isset($ctdata['fkey'][$fkey])) {
//                            $this->renderFkey($tablePrefix, $fkey, $rtable, $prefix, $suffix);
//                        }
//                    }
//                }

                if (!empty($tdata['ukey'])) {
                    foreach ($tdata['ukey'] as $ukey) {
                        if (!isset($ctdata['ukey'][$ukey])) {
                            $this->renderUkey($ukey, $prefix, $suffix);
                        }
                    }
                }

                if (!empty($tdata['pkey'])) {
                    if (!isset($ctdata['pkey'])) {
                        $this->sql .= $prefix . 'PRIMARY KEY' . $suffix;
                    }
                }
            } else {
                $this->sql .= "{$cmt}DROP TABLE {$tablePrefix}$tname;\n";
            }
        }

        foreach ($this->schema as $tname => $tdata) {
            if (isset($this->compare[$tname])) {
                $ctdata = $this->compare[$tname];
                $prefix = "\nALTER TABLE {$tablePrefix}$tname ADD ";

                if (!empty($tdata['cols'])) {
                    foreach ($tdata['cols'] as $cname => $cdata) {
                        if (isset($ctdata['cols'][$cname])) {
                            if ($cdata != $ctdata['cols'][$cname]) {
                                $prefixPkey = "{$cmt}ALTER TABLE {$tablePrefix}$tname MODIFY ";
                                $this->renderCol($cname, $cdata, $tdata, $prefixPkey, $suffix);
                            }
                        } else {
                            $this->renderCol($cname, $cdata, $tdata, $prefix, $suffix);
                        }
                    }
                }

                if (!empty($tdata['fkey'])) {
                    foreach ($tdata['fkey'] as $fkey => $rtable) {
                        if (isset($ctdata['fkey'][$fkey])) {
                            $cfkey = $ctdata['fkey'][$fkey];
                            if ($fkey != $cfkey) {
                                $this->renderFkey($tablePrefix, $fkey, $rtable, $prefix, $suffix);
                            }
                        } else {
                            $this->renderFkey($tablePrefix, $fkey, $rtable, $prefix, $suffix);
                        }
                    }
                }

                if (!empty($tdata['ukey'])) {
                    foreach ($tdata['ukey'] as $ukey) {
                        if (!isset($ctdata['ukey'][$ukey])) {
                            $this->renderUkey($ukey, $prefix, $suffix);
                        }
                    }
                }

                if (!empty($tdata['pkey'])) {
                    if (isset($ctdata['pkey'])) {
                        if ($tdata['pkey'] != $ctdata['pkey']) {
                            $prefixPkey = "{$cmt}ALTER TABLE {$tablePrefix}$tname DROP PRIMARY KEY, ADD ";
                            $this->renderPkey($tdata, $prefixPkey, $suffix);
                        }
                    } else {
                        $this->renderPkey($tdata, $prefix, $suffix);
                    }
                }
            } else {
                $this->renderTable($tablePrefix, $tname, $tdata);
            }
        }

        return $this->sql;
    }

}
