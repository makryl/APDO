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
     * @var \aeqdev\APDO\Schema
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
        $importer->readSQL($sql);
        $this->compare = $importer->getSchema();
    }

    public function compareWithSQLFile($file)
    {
        $importer = new Importer();
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
            foreach ($pkey as $pk) {
                $this->schema[$tname]['pkey'] [] = $pk;
            }

            if (!empty($table->cols)) {
                foreach ($table->cols as $cname) {
                    $col = $table->{$cname}();

                    $this->schema[$tname]['cols'][$cname]['type'] = $this->typeByClassColumn[get_class($col)];

                    if (!empty($col->length)) {
                        $this->schema[$tname]['cols'][$cname]['length'] = $col->length;
                    }

                    $this->schema[$tname]['cols'][$cname]['null']
                        = in_array($cname, $pkey)
                        ? false
                        : (isset($col->null) ? $col->null : true);
                }
            }

            if (!empty($table->ukey)) {
                $this->schema[$tname]['ukey'] = $table->ukey;
            }

            if (!empty($table->fkey)) {
                $this->schema[$tname]['fkey'] = $table->fkey;
                foreach ($table->fkey as $rtable => $fkey) {
                    $this->schema[$rtable]['refs'][$tname] = $tname;
                }
            }
        }
    }

    public function getSQL($withDrops = false)
    {
        if (isset($this->compare)) {
            return $this->exportDiffSQL($withDrops);
        } else {
            return $this->exportFullSQL();
        }
    }

    public function getFullSQL()
    {
        $this->sql = '';
        $prefix = $this->readSchema->prefix;

        foreach ($this->schema as $tname => $tdata) {
            $this->sql .= "CREATE TABLE {$prefix}$tname (\n";

            if (!empty($tdata['cols'])) {
                foreach ($tdata['cols'] as $cname => $cdata) {
                    $type = $this->sqlTypes[$cdata['type']];
                    $length = empty($cdata['length']) ? '' : '(' . $cdata['length'] . ')';
                    $null = empty($cdata['null']) ? ' NOT NULL' : '';
                    $suffix = '';

                    if (in_array($cname, $tdata['pkey'])) {
                        if ($this->pkeyAutoIncrement) {
                            $suffix = ' AUTO_INCREMENT';
                        }
                        if ($this->pkeyTypeSerial) {
                            $type = 'serial';
                        }
                    }

                    $this->sql .= "    $cname $type{$length}$null{$suffix},\n";
                }
            }

            $this->sql .= "\n";

            if (!empty($tdata['fkey'])) {
                foreach ($tdata['fkey'] as $rtable => $fkey) {
                    $rid = $this->schema[$rtable]['pkey'][0];
                    $this->sql .= "    FOREIGN KEY ($fkey) REFERENCES {$prefix}$rtable($rid),\n";
                }
            }

            if (!empty($tdata['ukey'])) {
                foreach ($tdata['ukey'] as $ukey) {
                    $this->sql .= "    UNIQUE KEY ($ukey),\n";
                }
            }

            $pkey = implode(', ', $tdata['pkey']);
            $this->sql .= "    PRIMARY KEY ($pkey)\n";

            $this->sql .= ");\n\n";
        }

        return $this->sql;
    }

    protected function getDiffSQL($withDrops = false)
    {
        $this->sql = '';
        return $this->sql;
    }

}
