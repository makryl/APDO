<?php

namespace aeqdev\APDO\Schema;

require_once __DIR__ . '/../../../../aeqdev/APDO/Schema/Builder.php';

class BuilderMock extends Builder
{
    public function getSchema()
    {
        return $this->schema;
    }
}

class BuilderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \aeqdev\APDO\Schema\BuilderMock
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new BuilderMock();
        $this->object->prefix = 'apdo_test_';
    }

    protected function tearDown()
    {
    }

    public function testRead()
    {
        $this->object->read(__DIR__ . '/../Schema.sql');
        $this->assertEquals([
            'tree' =>
            [
                'cols' =>
                [
                    'id' =>
                    [
                        'type' => 'int',
                        'null' => false,
                    ],
                    'name' =>
                    [
                        'type' => 'string',
                        'length' => '20',
                        'null' => true,
                    ],
                ],
                'pkey' =>
                [
                    0 => 'id',
                ],
                'refs' =>
                [
                    'tree_extra' => 'tree_extra',
                    'fruit' => 'fruit',
                ],
            ],
            'tree_extra' =>
            [
                'cols' =>
                [
                    'id' =>
                    [
                        'type' => 'int',
                        'null' => false,
                    ],
                    'height' =>
                    [
                        'type' => 'int',
                        'null' => true,
                    ],
                    'tree_id' =>
                    [
                        'type' => 'int',
                        'null' => false,
                    ],
                ],
                'ukey' =>
                [
                    'tree_id' => 'tree_id',
                ],
                'fkey' =>
                [
                    'tree' => 'tree_id',
                ],
                'pkey' =>
                [
                    0 => 'id',
                ],
            ],
            'fruit' =>
            [
                'cols' =>
                [
                    'id' =>
                    [
                        'type' => 'int',
                        'null' => false,
                    ],
                    'name' =>
                    [
                        'type' => 'string',
                        'length' => '20',
                        'null' => false,
                    ],
                    'color' =>
                    [
                        'type' => 'string',
                        'length' => '5',
                        'null' => true,
                    ],
                    'tree_id' =>
                    [
                        'type' => 'int',
                        'null' => true,
                    ],
                ],
                'fkey' =>
                [
                    'tree' => 'tree_id',
                ],
                'pkey' =>
                [
                    0 => 'id',
                ],
            ],
        ], $this->object->getSchema());
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
