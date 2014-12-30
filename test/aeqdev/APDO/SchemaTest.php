<?php

namespace aeqdev\APDO\Schema;

use PDO;
use test\aeqdev\APDO\ArraySerializeCache;
use test\aeqdev\APDO\Schema;

require_once '../../autoload.php';

class SchemaTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Schema
     */
    protected $object;

    /**
     * @var ArraySerializeCache
     */
    protected $cache;

    protected function setUp()
    {
        $this->object = new Schema('mysql:host=localhost;dbname=test', 'root', '', [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $sql = file_get_contents(__DIR__ . '/Schema-drop.sql')
            . file_get_contents(__DIR__ . '/Schema.sql')
            . file_get_contents(__DIR__ . '/Schema-insert.sql');

        foreach (explode(';', $sql) as $statement) {
            if (trim($statement) != '') {
                $this->object->statement($statement)->execute();
            }
        }

        $this->cache = new ArraySerializeCache();
    }

    protected function tearDown()
    {
        if (isset($this->object)) {
            $sql = file_get_contents(__DIR__ . '/Schema-drop.sql');

            foreach (explode(';', $sql) as $statement) {
                if (trim($statement) != '') {
                    $this->object->statement($statement)->execute();
                }
            }
        }
    }

    public function testSchemaGetTable()
    {
        $this->assertEquals('tree', $this->object->tree->name);
        $this->assertEquals('tree_extra', $this->object->tree_extra->name);
        $this->assertEquals('fruit', $this->object->fruit->name);
    }

    public function testSchemaCallStatement()
    {
        $this->assertEquals("SELECT *\nFROM apdo_test_tree", $this->object->tree()->buildSelect());
        $this->assertEquals("SELECT *\nFROM apdo_test_tree_extra", $this->object->tree_extra()->buildSelect());
        $this->assertEquals("SELECT *\nFROM apdo_test_fruit", $this->object->fruit()->buildSelect());
    }

    public function testTableCallColumn()
    {
        $col_tree_name = $this->object->tree->name();
        $this->assertEquals('name', $col_tree_name->name);
        $this->assertInstanceOf('\\aeqdev\\APDO\\Schema\\Column\\String', $col_tree_name);

        $col_tree_extra_height = $this->object->tree_extra->height();
        $this->assertEquals('height', $col_tree_extra_height->name);
        $this->assertInstanceOf('\\aeqdev\\APDO\\Schema\\Column\\Int', $col_tree_extra_height);

        $col_fruit_tree = $this->object->fruit->tree();
        $this->assertEquals('tree', $col_fruit_tree->name);
        $this->assertInstanceOf('\\aeqdev\\APDO\\Schema\\Column\\Int', $col_fruit_tree);
    }

    public function testTableCreateAndRowSave()
    {
        $fruit = $this->object->fruit->create();
        $fruit->name = 'new fruit';
        $fruit->save();

        $this->assertNotEmpty($fruit->id);

        $fruit->name = 'new fruit updated';
        $fruit->save();

        $check_fruit = $this->object->fruit->get($fruit->id);
        $this->assertNotNull($check_fruit);
        $this->assertEquals($check_fruit->id, $fruit->id);
        $this->assertEquals($check_fruit->name, $fruit->name);
    }

    public function testTableGet()
    {
        $fruit = $this->object->fruit->get(2);
        $this->assertEquals('apple2', $fruit->name);
    }

    public function testStatementFetchAll()
    {
        foreach ($this->object->tree()->fetchAll() as $tree) {
            $this->assertInstanceOf('\\test\\aeqdev\\APDO\\Row_tree', $tree);
        }

        foreach ($this->object->tree_extra()->fetchAll() as $tree_extra) {
            $this->assertInstanceOf('\\test\\aeqdev\\APDO\\Row_tree_extra', $tree_extra);
        }

        foreach ($this->object->fruit()->fetchAll() as $fruit) {
            $this->assertInstanceOf('\\test\\aeqdev\\APDO\\Row_fruit', $fruit);
        }
    }

    public function testStatementCall()
    {
        $fruits = $this->object->tree()->fruit()->fetchAll();

        $this->assertEquals('apple1', $fruits[0]->name);
        $this->assertEquals('apple2', $fruits[1]->name);
        $this->assertEquals('orange', $fruits[2]->name);
    }

    public function testRowCall()
    {
        $tree = $this->object->tree->get(1);

        $this->assertEquals(10, $tree->tree_extra__tree()->height);

        $fruits = $tree->fruit()->fetchAll();
        $this->assertEquals('apple1', $fruits[0]->name);
        $this->assertEquals('apple2', $fruits[1]->name);
    }

    public function testResultCall()
    {
        $trees = $this->object->tree()->addOrderBy('id')->fetchAll();
        $trees->fruit()->fetchAll();
        $trees->tree_extra__tree()->fetchAll();

        $this->assertEquals(10, $trees[0]->tree_extra__tree->height);
        $this->assertEquals('apple1', $trees[0]->fruit[0]->name);
        $this->assertEquals('apple2', $trees[0]->fruit[1]->name);

        $this->assertEquals(20, $trees[1]->tree_extra__tree->height);
        $this->assertEquals('orange', $trees[1]->fruit[0]->name);

        $fruits = $this->object->fruit()->fetchAll();
        $this->assertNull($fruits->tree_extra());
    }

    public function testColumnValidation()
    {
        $fruit = $this->object->fruit->create();
        $fruit->color = 'yellow';
        $this->assertEquals('yello', $this->object->fruit->color()->value($fruit)); # because of limit 5 chars
    }

    public function testCache()
    {
        $fruits = $this->object->fruit()
            ->cache($this->cache)
            ->fetchAll();

        $this->assertEquals('apple1', $fruits[0]->name);
        $this->assertEquals('apple2', $fruits[1]->name);
        $this->assertEquals('orange', $fruits[2]->name);

        $executedCount = $this->object->executedCount();

        $fruits = $this->object->fruit()
            ->cache($this->cache)
            ->fetchAll();

        $this->assertEquals('apple1', $fruits[0]->name);
        $this->assertEquals('apple2', $fruits[1]->name);
        $this->assertEquals('orange', $fruits[2]->name);

        $this->assertInstanceOf(__NAMESPACE__ . '\\Table', $fruits[0]->table);

        $this->assertEquals($executedCount, $this->object->executedCount());
    }

}
