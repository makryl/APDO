<?php

namespace aeqdev;

require '../APDO.php';
require '../apdo/ICache.php';
require '../apdo/ArrayCache.php';



class APDOTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var APDO
     */
    protected $object;

    /**
     * @var APDOCacheArray
     */
    protected $cache;



    protected function setUp()
    {
        $this->object = new APDO('mysql:host=localhost;dbname=test', 'root', '', [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"',
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);

        $this->object->statement('DROP TABLE IF EXISTS apdo_test_tree')->execute();
        $this->object->statement('CREATE TABLE apdo_test_tree (id int NOT NULL PRIMARY KEY, name varchar(20))')->execute();
        $this->object->statement("INSERT INTO apdo_test_tree (id, name) VALUES (1, 'apple tree')")->execute();
        $this->object->statement("INSERT INTO apdo_test_tree (id, name) VALUES (2, 'orange tree')")->execute();

        $this->object->statement('DROP TABLE IF EXISTS apdo_test_fruit')->execute();
        $this->object->statement('CREATE TABLE apdo_test_fruit (id int NOT NULL PRIMARY KEY, name varchar(20), tree int)')->execute();
        $this->object->statement("INSERT INTO apdo_test_fruit (id, name, tree) VALUES (1, 'apple1', 1)")->execute();
        $this->object->statement("INSERT INTO apdo_test_fruit (id, name, tree) VALUES (2, 'apple2', 1)")->execute();
        $this->object->statement("INSERT INTO apdo_test_fruit (id, name, tree) VALUES (3, 'orange', 2)")->execute();

        $this->cache = new apdo\ArrayCache();
    }



    protected function tearDown()
    {
        if (isset($this->object))
        {
            $this->object->statement('DROP TABLE IF EXISTS apdo_test_tree')->execute();
            $this->object->statement('DROP TABLE IF EXISTS apdo_test_fruit')->execute();
        }
    }



    function testExecutedCount()
    {
        $old_executedCount = $this->object->executedCount();
        $this->object->statement('SELECT 1')->all();
        $this->object->statement('SELECT 2')->execute();
        $this->assertEquals(2, $this->object->executedCount() - $old_executedCount);
    }



    function testLastQuery()
    {
        $this->object->statement('SELECT 111')->all();
        $this->assertEquals('SELECT 111', $this->object->last()->lastQuery());

        $this->object->statement('SELECT 222')->execute();
        $this->assertEquals('SELECT 222', $this->object->last()->lastQuery());
    }



    function testExecute()
    {
        $errmode = $this->object->pdo()->getAttribute(\PDO::ATTR_ERRMODE);
        $this->object->pdo()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

        $r = $this->object->statement('INSERT INTO apdo_test_fruit (id) VALUES (123)')->execute();
        $this->assertEquals(true, $r);

        $r = $this->object->statement('INSERT INTO apdo_test_fruit (id) VALUES (123)')->execute();
        $this->assertEquals(false, $r);

        $this->object->pdo()->setAttribute(\PDO::ATTR_ERRMODE, $errmode);
    }



    function testCache()
    {
        $statement = 'SELECT * FROM apdo_test_fruit ORDER BY id LIMIT 1';
        $result = [0 => ['id' => 1, 'name' => 'apple1', 'tree' => 1]];

        $r = $this->object->statement($statement)
            ->cache($this->cache)
            ->all();
        $this->assertEquals($result, $r);

        $executedCount = $this->object->executedCount();

        $r = $this->object->statement($statement)
            ->cache($this->cache)
            ->all();
        $this->assertEquals($result, $r);
        $this->assertEquals($executedCount, $this->object->executedCount());
    }



    function testPkey()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->pkey('id')
            ->key(1)
            ->one();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE id=?
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 1, 'name' => 'apple1', 'tree' => 1], $r);
    }



    function testJoin()
    {
        $r = $this->object
            ->from('apdo_test_fruit A')
            ->join('apdo_test_tree B', 'A.tree=B.id')
            ->fields('A.name as fruit_name, B.name as tree_name')
            ->orderBy('A.id')
            ->one();
        $this->assertEquals(
            'SELECT A.name as fruit_name, B.name as tree_name
FROM apdo_test_fruit A
 JOIN apdo_test_tree B ON (A.tree=B.id)
ORDER BY A.id
LIMIT 1',
            $this->object->last()->lastQuery());
        $this->assertEquals(['fruit_name' => 'apple1', 'tree_name' => 'apple tree'], $r);
    }



    function testWhere()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->where('id=?', 2)
            ->one();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE id=?
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 2, 'name' => 'apple2', 'tree' => 1], $r);
    }



    function testOrWhere()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->where('id=?', 2)
            ->orWhere('id=?', 3)
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE (id=?) OR (id=?)', $this->object->last()->lastQuery());
        $this->assertEquals([
            ['id' => 2, 'name' => 'apple2', 'tree' => 1],
            ['id' => 3, 'name' => 'orange', 'tree' => 2],
        ], $r);
    }



    function testKey()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->key([2, 3])
            ->orderBy('id')
            ->one();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE id IN (?,?)
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 2, 'name' => 'apple2', 'tree' => 1], $r);
    }



    function testOrKey()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->key([2, 3])
            ->orKey([1, 2])
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE (id IN (?,?)) OR (id IN (?,?))', $this->object->last()->lastQuery());
        $this->assertEquals([
            ['id' => 1, 'name' => 'apple1', 'tree' => 1],
            ['id' => 2, 'name' => 'apple2', 'tree' => 1],
            ['id' => 3, 'name' => 'orange', 'tree' => 2]
        ], $r);
    }



    function testOrderby()
    {
        $this->object->statement("INSERT INTO apdo_test_fruit (id, name, tree) VALUES (999999, 'apple9', 1)")->execute();
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id DESC')
            ->one();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id DESC
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 999999, 'name' => 'apple9', 'tree' => 1], $r);
    }



    function testAddOrderby()
    {
        $this->object->statement("INSERT INTO apdo_test_fruit (id, name, tree) VALUES (999999, 'apple9', 1)")->execute();
        $r = $this->object
            ->from('apdo_test_fruit')
            ->addOrderBy('id', true)
            ->one();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id DESC
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 999999, 'name' => 'apple9', 'tree' => 1], $r);
    }



    function testLimit()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->limit(2)
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
LIMIT 2', $this->object->last()->lastQuery());
        $this->assertEquals(2, count($r));
    }



    function testOffset()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->limit(1)
            ->offset(1)
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1
OFFSET 1', $this->object->last()->lastQuery());
        $this->assertEquals([0 => ['id' => 2, 'name' => 'apple2', 'tree' => 1]], $r);
    }



    function testPage()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->limit(1)
            ->page(2);
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1
OFFSET 1', $this->object->last()->lastQuery());
        $this->assertEquals([0 => ['id' => 2, 'name' => 'apple2', 'tree' => 1]], $r);
    }



    function testFields()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->fields(['id', 'id AS id2'])
            ->orderBy('id')
            ->one();
        $this->assertEquals(
            'SELECT id, id AS id2
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 1, 'id2' => 1], $r);
    }



    function testOne()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->one();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals(['id' => 1, 'name' => 'apple1', 'tree' => 1], $r);
    }



    function testOneL()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->oneL();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals([0 => 1, 1 => 'apple1', 2 => 1], $r);
    }



    function testAll()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->limit(1)
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals([0 => ['id' => 1, 'name' => 'apple1', 'tree' => 1]], $r);
    }



    function testAllK()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->fields('id, id')
            ->orderBy('id')
            ->limit(1)
            ->allK();
        $this->assertEquals(
            'SELECT id, id
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals([1 => 1], $r);
    }



    function testKeys()
    {
        $r = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->limit(1)
            ->keys('id');
        $this->assertEquals(
            'SELECT id, id
FROM apdo_test_fruit
ORDER BY id
LIMIT 1', $this->object->last()->lastQuery());
        $this->assertEquals([1 => 1], $r);
    }



    function testCount()
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



    function testInsert()
    {
        $this->object
            ->in('apdo_test_fruit')
            ->insert([
                ['id' => 11, 'name' => 'apple11', 'tree' => 1],
                ['id' => 12, 'name' => 'apple12', 'tree' => 1]
            ]);
        $this->assertEquals(
            'INSERT INTO apdo_test_fruit (id,name,tree) VALUES
(?,?,?),
(?,?,?)', $this->object->last()->lastQuery());

        $r = $this->object
            ->from('apdo_test_fruit')
            ->key([11, 12])
            ->all();
        $this->assertEquals([
            ['id' => 11, 'name' => 'apple11', 'tree' => 1],
            ['id' => 12, 'name' => 'apple12', 'tree' => 1]
        ], $r);
    }



    function testUpdate()
    {
        $this->object
            ->in('apdo_test_fruit')
            ->insert(['id' => 21, 'name' => 'apple21', 'tree' => 1]);

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
            ->one();
        $this->assertEquals(['id' => 21, 'name' => 'apple22', 'tree' => 1], $r);
    }



    function testDelete()
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
            ->one();
        $this->assertEquals(null, $r);
    }



    function testReferers()
    {
        $fruits = $this->object
            ->from('apdo_test_fruit')
            ->orderBy('id')
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
ORDER BY id', $this->object->last()->lastQuery());

        $trees = $this->object
            ->from('apdo_test_tree')
            ->referrers($fruits, 'fruits', 'tree')
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_tree
WHERE id IN (?,?)', $this->object->last()->lastQuery());

        $this->checkReferences($fruits, $trees);
    }



    function testReferences()
    {
        $trees = $this->object
            ->from('apdo_test_tree')
            ->orderBy('id')
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_tree
ORDER BY id', $this->object->last()->lastQuery());

        $fruits = $this->object
            ->from('apdo_test_fruit')
            ->references($trees, 'fruits', 'tree')
            ->all();
        $this->assertEquals(
            'SELECT *
FROM apdo_test_fruit
WHERE tree IN (?,?)', $this->object->last()->lastQuery());

        $this->checkReferences($fruits, $trees);
    }



    function checkReferences($fruits, $trees)
    {
        foreach ($fruits as $fruit)
        {
            switch ($fruit['id'])
            {
                case 1:
                case 2:
                    $this->assertEquals('apple tree', $fruit['tree']['name']);
                    break;
                case 3:
                    $this->assertEquals('orange tree', $fruit['tree']['name']);
                    break;
            }
        }

        foreach ($trees as $tree)
        {
            $this->assertArrayHasKey('fruits', $tree);
            switch ($fruit['id'])
            {
                case 1:
                    $this->assertEquals('apple1', $tree['fruits'][0]);
                    $this->assertEquals('apple2', $tree['fruits'][1]);
                    break;
                case 2:
                    $this->assertEquals('orange', $tree['fruits'][0]);
                    break;
            }
        }
    }

}
