<?php

namespace aeqdev\APDO\Schema;

require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Importer.php';

class ImporterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \aeqdev\APDO\Schema\ImporterMock
     */
    protected $object;
    protected $schemaInternal;

    protected function setUp()
    {
        $this->object = new Importer();
        $this->object->prefix = 'apdo_test_';
        $this->schemaInternal = include __DIR__ . '/../SchemaInternal.php';
    }

    protected function tearDown()
    {
    }

    public function testRead()
    {
        $this->object->read(__DIR__ . '/../Schema.sql');
        $this->assertEquals($this->schemaInternal, $this->object->getSchema());
    }

    public function testSave()
    {
        $this->object->read(__DIR__ . '/../Schema.sql');
        $tmpname = tempnam(null, 'apdo_test_');
        $this->object->save($tmpname, '\\test\\Schema');
        $this->assertFileEquals(__DIR__ . '/../Schema.php', $tmpname);
        unlink($tmpname);
    }

}
