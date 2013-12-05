<?php

namespace aeqdev\APDO\Schema;

require_once __DIR__ . '/../../../../aeqdev/APDO.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Table.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Statement.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Row.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Exporter.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Importer.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Column.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Column/Time.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Column/Date.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Column/Bool.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Column/Int.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Column/Float.php';
require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Column/String.php';
require_once __DIR__ . '/../Schema.php';

class ExporterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \aeqdev\APDO\Schema\ExporterMock
     */
    protected $object;

    /**
     * @var \test\Schema
     */
    protected $schema;
    protected $schemaInternal;

    protected function setUp()
    {
        $this->object = new Exporter();
        $this->object->pkeyAutoIncrement = true;

        $this->schemaInternal = include __DIR__ . '/../SchemaInternal.php';
        
        $this->schema = new \test\Schema('mysql:host=localhost;dbname=test', 'root', 'root', [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"',
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
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

}
