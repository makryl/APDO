<?php

namespace aeqdev\APDO\Schema;

use PDO;
use test\aeqdev\APDO\Schema;

require_once '../../../autoload.php';

class ExporterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Exporter
     */
    protected $object;

    /**
     * @var Schema
     */
    protected $schema;
    protected $schemaInternal;

    protected function setUp()
    {
        $this->object = new Exporter();
        $this->object->pkeyAutoIncrement = true;

        $this->schemaInternal = include __DIR__ . '/../SchemaInternal.php';

        $this->schema = new Schema('mysql:host=localhost;dbname=test', 'root', 'root', [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    protected function tearDown()
    {
    }

    public function testReadSchema()
    {
        $this->object->readSchema($this->schema);
        $this->assertEquals($this->schemaInternal, $this->object->getSchema());
    }

    public function testGetFullSQL()
    {
        $this->object->readSchema($this->schema);
        $this->assertEquals(file_get_contents(__DIR__ . '/../Schema.sql'), $this->object->getFullSQL());
    }

    public function testGetDiffSQL()
    {
        $this->object->readSchema($this->schema);
        $this->object->compareWithSQLFile(__DIR__ . '/../Schema-compare.sql');
        $this->assertEquals(file_get_contents(__DIR__ . '/../Schema-diff.sql'), $this->object->getDiffSQL());
    }

}
