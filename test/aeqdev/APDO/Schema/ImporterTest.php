<?php

namespace aeqdev\APDO\Schema;

require_once __DIR__ . '/../../../autoload.php';

class ImporterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Importer
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
        unlink($tmpname);
        mkdir($tmpname);
        $this->object->save('\\test\\aeqdev\\APDO\\TestSchema', $tmpname);

        $testFile = $tmpname . '/test/aeqdev/APDO/TestSchema.php';
        $this->assertFileEquals(
            __DIR__ . '/../TestSchema.php',
            $testFile
        );
        unlink($testFile);

        foreach (new \DirectoryIterator(__DIR__ . '/../TestSchema') as $file) {
            if ($file->isFile()) {
                $testFile = $tmpname . '/test/aeqdev/APDO/TestSchema/' . $file->getFilename();
                $this->assertFileEquals(
                    $file->getPathname(),
                    $testFile
                );
                unlink($testFile);
            }
        }

        foreach (new \DirectoryIterator(__DIR__ . '/../TestSchema/generated') as $file) {
            if ($file->isFile()) {
                $testFile = $tmpname . '/test/aeqdev/APDO/TestSchema/generated/' . $file->getFilename();
                $this->assertFileEquals(
                    $file->getPathname(),
                    $testFile
                );
                unlink($testFile);
            }
        }

        rmdir($tmpname . '/test/aeqdev/APDO/TestSchema/generated');
        rmdir($tmpname . '/test/aeqdev/APDO/TestSchema');
        rmdir($tmpname . '/test/aeqdev/APDO');
        rmdir($tmpname . '/test/aeqdev');
        rmdir($tmpname . '/test');
        rmdir($tmpname);
    }

}
