<?php

namespace aeqdev;

require_once __DIR__ . '/../../aeqdev/APDO.php';
require_once __DIR__ . '/../../aeqdev/APDO/ICache.php';
require_once __DIR__ . '/APDO/ArraySerializeCache.php';



class APDOTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var APDO
     */
    protected $object;

    /**
     * @var \aeqdev\APDO\ArraySerializeCache
     */
    protected $cache;

    protected function setUp()
    {
        $this->object = new APDO('mysql:host=localhost;dbname=test', 'root', 'root', [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"',
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);

        $sql = file_get_contents(__DIR__ . '/APDO/Schema-drop.sql')
            . file_get_contents(__DIR__ . '/APDO/Schema.sql')
            . file_get_contents(__DIR__ . '/APDO/Schema-insert.sql');

        foreach (explode(';', $sql) as $statement) {
            if (trim($statement) != '') {
                $this->object->statement($statement)->execute();
            }
        }

        $this->cache = new APDO\ArraySerializeCache();

        $this->object->setFetchMode(\PDO::FETCH_ASSOC);
    }

    protected function tearDown()
    {
        if (isset($this->object)) {
            $sql = file_get_contents(__DIR__ . '/APDO/Schema-drop.sql');

            foreach (explode(';', $sql) as $statement) {
                if (trim($statement) != '') {
                    $this->object->statement($statement)->execute();
                }
            }
        }
    }

    public function testExecutedCount()
    {
        $old_executedCount = $this->object->executedCount();
        $this->object->statement('SELECT 1')->fetchAll();
        $this->object->statement('SELECT 2')->execute();
        $this->assertEquals(2, $this->object->executedCount() - $old_executedCount);
    }

    public function testLastQuery()
    {
        $this->object->statement('SELECT 111')->fetchAll();
        $this->assertEquals('SELECT 111', $this->object->last()->lastQuery());

        $this->object->statement('SELECT 222')->execute();
        $this->assertEquals('SELECT 222', $this->object->last()->lastQuery());
    }

    public function testExecute()
    {
        $errmode = $this->object->pdo()->getAttribute(\PDO::ATTR_ERRMODE);
        $this->object->pdo()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

        $r = $this->object->statement('INSERT INTO apdo_test_fruit (id) VALUES (123)')->execute();
        $this->assertEquals(true, $r);

        $r = $this->object->statement('INSERT INTO apdo_test_fruit (id) VALUES (123)')->execute();
        $this->assertEquals(false, $r);

        $this->object->pdo()->setAttribute(\PDO::ATTR_ERRMODE, $errmode);
    }

    public function testCache()
    {
        $statement = 'SELECT * FROM apdo_test_fruit ORDER BY id LIMIT 1';
        $result = [0 => ['id' => 1, 'name' => 'apple1', 'color' => null, 'tree_id' => 1]];

        $r = $this->object->statement($statement)
            ->cache($this->cache)
            ->fetchAll();
        $this->assertEquals($result, $r);

        $executedCount = $this->object->executedCount();

        $r = $this->object->statement($statement)
            ->cache($this->cache)
            ->fetchAll();
        $this->assertEquals($result, $r);
        $this->assertEquals($executedCount, $this->object->executedCount());
    }

    public function testPkey()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->pkey('id')
            ->key(1)
            ->fetchOne();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE id=?
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 1, 'name' => 'apple1', 'color' => null, 'tree_id' => 1], $r);
    }

    public function testJoin()
    {
        $r = $this->object
            ->from('apdo_test_fruit A')
            ->join('apdo_test_tree B', 'A.tree_id=B.id')
            ->columns('A.name as fruit_name, B.name as tree_name')
            ->orderBy('A.id')
            ->fetchOne();
        $this->assertEquals(
            'SELECT A.name as fruit_name, B.name as tree_name
FROM apdo_test_fruit A
 JOIN apdo_test_tree B ON (A.tree_id=B.id)
ORDER BY A.id
LIMIT 1',
            $this->object->last()->lastQuery());
        $this->assertEquals(['fruit_name' => 'apple1', 'tree_name' => 'apple tree'], $r);
    }

    public function testWhere()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->where('id=?', 2)
            ->fetchOne();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE id=?
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 2, 'name' => 'apple2', 'color' => null, 'tree_id' => 1], $r);
    }

    public function testOrWhere()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->where('id=?', 2)
            ->orWhere('id=?', 3)
            ->fetchAll();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE (id=?) OR (id=?)', $this->object->last()->lastQuery());
        $this->assertEquals([
            ['id' => 2, 'name' => 'apple2', 'color' => null, 'tree_id' => 1],
            ['id' => 3, 'name' => 'orange', 'color' => null, 'tree_id' => 2],
        ], $r);
    }

    public function testKey()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->key([2, 3])
            ->orderBy('id')
            ->fetchOne();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE id IN (?,?)
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 2, 'name' => 'apple2', 'color' => null, 'tree_id' => 1], $r);
    }

    public function testOrKey()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->key([2, 3])
            ->orKey([1, 2])
            ->fetchAll();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE (id IN (?,?)) OR (id IN (?,?))', $this->object->last()->lastQuery());
        $this->assertEquals([
            ['id' => 1, 'name' => 'apple1', 'color' => null, 'tree_id' => 1],
            ['id' => 2, 'name' => 'apple2', 'color' => null, 'tree_id' => 1],
            ['id' => 3, 'name' => 'orange', 'color' => null, 'tree_id' => 2]
        ], $r);
    }

    public function testOrderby()
    {
        $this->object->statement("INSERT INTO apdo_test_fruit (id, name, tree_id) VALUES (999999, 'apple9', 1)")->execute();
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id DESC')
            ->fetchOne();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id DESC
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 999999, 'name' => 'apple9', 'color' => null, 'tree_id' => 1], $r);
    }

    public function testAddOrderby()
    {
        $this->object->statement("INSERT INTO apdo_test_fruit (id, name, tree_id) VALUES (999999, 'apple9', 1)")->execute();
        $r = $this->object
            ->from('apdo_test_fruit')
            ->addOrderBy('id', true)
            ->fetchOne();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id DESC
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 999999, 'name' => 'apple9', 'color' => null, 'tree_id' => 1], $r);
    }

    public function testLimit()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->limit(2)
            ->fetchAll();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
LIMIT 2', $this->object->last()->lastQuery());
        $this->assertEquals(2, count($r));
    }

    public function testOffset()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->limit(1)
            ->offset(1)
            ->fetchAll();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1
OFFSET 1', $this->object->last()->lastQuery());
        $this->assertEquals([0 => ['id' => 2, 'name' => 'apple2', 'color' => null, 'tree_id' => 1]], $r);
    }

    public function testColumns()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->columns(['id', 'id AS id2'])
            ->orderBy('id')
            ->fetchOne();
        $this->assertEquals(
            'SELECT id, id AS id2
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 1, 'id2' => 1], $r);
    }

    public function testFetchAll()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->limit(1)
            ->fetchAll();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals([0 => ['id' => 1, 'name' => 'apple1', 'color' => null, 'tree_id' => 1]], $r);
    }

    public function testFetchPairs()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->columns('id, id')
            ->orderBy('id')
            ->limit(1)
            ->fetchPairs();
        $this->assertEquals(
            'SELECT id, id
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals([1 => 1], $r);
    }

    public function testFetchPage()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->limit(1)
            ->fetchPage(2);
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1
OFFSET 1', $this->object->last()->lastQuery());
        $this->assertEquals([0 => ['id' => 2, 'name' => 'apple2', 'color' => null, 'tree_id' => 1]], $r);
    }

    public function testFetchOne()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->fetchOne();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 1, 'name' => 'apple1', 'color' => null, 'tree_id' => 1], $r);
    }

    public function testFetchRow()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->fetchRow(['id', 'name']);
        $this->assertEquals(
            'SELECT id, name
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals([0 => 1, 1 => 'apple1'], $r);
    }

    public function testFetchCell()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->fetchCell('name');
        $this->assertEquals(
            'SELECT name
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals('apple1', $r);
    }

    public function testCount()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->key([1, 2])
            ->count();
        $this->assertEquals(
            'SELECT COUNT(*)
FROM apdo_test_fruit
WHERE id IN (?,?)', $this->object->last()->lastQuery());
        $this->assertEquals(2, $r);
    }

    public function testInsert()
    {
        $this->object
            ->in('apdo_test_fruit')
            ->insert([
                ['id' => 11, 'name' => 'apple11', 'tree_id' => 1],
                ['id' => 12, 'name' => 'apple12', 'tree_id' => 1]
            ]);
        $this->assertEquals(
            'INSERT INTO apdo_test_fruit (id,name,tree_id) VALUES
(?,?,?),
(?,?,?)', $this->object->last()->lastQuery());

        $r = $this->object
            ->from('apdo_test_fruit')
            ->key([11, 12])
            ->fetchAll();
        $this->assertEquals([
            ['id' => 11, 'name' => 'apple11', 'color' => null, 'tree_id' => 1],
            ['id' => 12, 'name' => 'apple12', 'color' => null, 'tree_id' => 1]
        ], $r);
    }

    public function testUpdate()
    {
        $this->object
            ->in('apdo_test_fruit')
            ->insert(['id' => 21, 'name' => 'apple21', 'tree_id' => 1]);

        $this->object
            ->in('apdo_test_fruit')
            ->where('id=?', 21)
            ->update(['name' => 'apple22']);

        $this->assertEquals(
            'UPDATE apdo_test_fruit
SET
    name=?
WHERE id=?', $this->object->last()->lastQuery());

        $r = $this->object
            ->from('apdo_test_fruit')
            ->key(21)
            ->fetchOne();
        $this->assertEquals(['id' => 21, 'name' => 'apple22', 'color' => null, 'tree_id' => 1], $r);
    }

    public function testDelete()
    {
        $this->object
            ->in('apdo_test_fruit')
            ->insert(['id' => 31]);

        $this->object
            ->from('apdo_test_fruit')
            ->where('id=?', 31)
            ->delete();

        $this->assertEquals(
            'DELETE FROM apdo_test_fruit
WHERE id=?', $this->object->last()->lastQuery());

        $r = $this->object
            ->from('apdo_test_fruit')
            ->key(31)
            ->fetchOne();
        $this->assertEquals(null, $r);
    }

    public function testReferers()
    {
        $fruits = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->fetchAll(\PDO::FETCH_OBJ);
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id', $this->object->last()->lastQuery());

        $trees = $this->object
            ->from('apdo_test_tree')
            ->referrers($fruits, 'fruits', 'tree', 'tree_id')
            ->fetchAll(\PDO::FETCH_OBJ);
        $this->assertEquals(
            'SELECT *
FROM apdo_test_tree
WHERE id IN (?,?)', $this->object->last()->lastQuery());

        $this->checkReferences($fruits, $trees);
    }

    public function testReferences()
    {
        $trees = $this->object
            ->from('apdo_test_tree')
            ->orderBy('id')
            ->fetchAll(\PDO::FETCH_OBJ);
        $this->assertEquals(
            'SELECT *
FROM apdo_test_tree
ORDER BY id', $this->object->last()->lastQuery());

        $fruits = $this->object
            ->from('apdo_test_fruit')
            ->references($trees, 'fruits', 'tree', 'tree_id')
            ->fetchAll(\PDO::FETCH_OBJ);
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE tree_id IN (?,?)', $this->object->last()->lastQuery());

        $this->checkReferences($fruits, $trees);
    }

    public function checkReferences($fruits, $trees)
    {
        foreach ($fruits as $fruit) {
            $this->assertObjectHasAttribute('tree', $fruit);
            switch ($fruit->id) {
                case 1:
                case 2:
                    $this->assertEquals('apple tree', $fruit->tree->name);
                    break;
                case 3:
                    $this->assertEquals('orange tree', $fruit->tree->name);
                    break;
            }
        }

        foreach ($trees as $tree) {
            $this->assertObjectHasAttribute('fruits', $tree);
            switch ($fruit->id) {
                case 1:
                    $this->assertEquals('apple1', $tree->fruits[0]->name);
                    $this->assertEquals('apple2', $tree->fruits[1]->name);
                    break;
                case 2:
                    $this->assertEquals('orange', $tree->fruits[0]->name);
                    break;
            }
        }
    }

}
