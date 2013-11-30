<?php

namespace test;

/**
 * @property \test\Table_tree $tree
 * @property \test\Table_tree_extra $tree_extra
 * @property \test\Table_fruit $fruit

 * @method \test\Statement_tree tree()
 * @method \test\Statement_tree_extra tree_extra()
 * @method \test\Statement_fruit fruit()
 */
class Schema extends \aeqdev\APDO\Schema
{
    public $prefix = 'apdo_test_';

    public $class_tree = '\\test\\Table_tree';
    public $class_tree_extra = '\\test\\Table_tree_extra';
    public $class_fruit = '\\test\\Table_fruit';
}

/**
 * @property \test\Schema $schema
 *
 * @method \test\Row_tree create()
 * @method \test\Row_tree get($id)
 *
 * @method \aeqdev\APDO\Schema\Column\Int id()
 * @method \aeqdev\APDO\Schema\Column\String name()
 */
class Table_tree extends \aeqdev\APDO\Schema\Table
{
    public $name = 'tree';
    public $pkey = 'id';
    public $cols = [
        'id' => 'id',
        'name' => 'name',
    ];

    public $class_row = '\\test\\Row_tree';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_name() { return (new \aeqdev\APDO\Schema\Column\String())->length(20); }
}

/**
 * @method \test\Table_tree table()

 * @method \test\Row_tree_extra tree_extra()
 * @method \test\Row_fruit[] fruit()
 */
class Row_tree extends \aeqdev\APDO\Schema\Row
{
    public $id;
    public $name;

    /** @var \test\Row_tree_extra */
    public $tree_extra;
    /** @var \test\Row_fruit[] */
    public $fruit = [];
}

/**
 * @property \test\Schema $schema
 *
 * @method \test\Row_tree_extra create()
 * @method \test\Row_tree_extra get($id)
 *
 * @method \aeqdev\APDO\Schema\Column\Int id()
 * @method \aeqdev\APDO\Schema\Column\Int height()
 * @method \aeqdev\APDO\Schema\Column\Int tree_id()
 */
class Table_tree_extra extends \aeqdev\APDO\Schema\Table
{
    public $name = 'tree_extra';
    public $pkey = 'id';
    public $ukey = [
        'tree_id' => 'tree_id',
    ];
    public $fkey = [
        'tree' => 'tree_id',
    ];
    public $rtable = [
        'tree_id' => 'tree',
    ];
    public $cols = [
        'id' => 'id',
        'height' => 'height',
        'tree_id' => 'tree_id',
    ];

    public $class_row = '\\test\\Row_tree_extra';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_height() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_tree_id() { return (new \aeqdev\APDO\Schema\Column\Int())->fkey()->required(); }
}

/**
 * @method \test\Table_tree_extra table()

 * @method \test\Row_tree tree()
 */
class Row_tree_extra extends \aeqdev\APDO\Schema\Row
{
    public $id;
    public $height;
    public $tree_id;

    /** @var \test\Row_tree */
    public $tree;
}

/**
 * @property \test\Schema $schema
 *
 * @method \test\Row_fruit create()
 * @method \test\Row_fruit get($id)
 *
 * @method \aeqdev\APDO\Schema\Column\Int id()
 * @method \aeqdev\APDO\Schema\Column\String name()
 * @method \aeqdev\APDO\Schema\Column\String color()
 * @method \aeqdev\APDO\Schema\Column\Int tree_id()
 */
class Table_fruit extends \aeqdev\APDO\Schema\Table
{
    public $name = 'fruit';
    public $pkey = 'id';
    public $fkey = [
        'tree' => 'tree_id',
    ];
    public $rtable = [
        'tree_id' => 'tree',
    ];
    public $cols = [
        'id' => 'id',
        'name' => 'name',
        'color' => 'color',
        'tree_id' => 'tree_id',
    ];

    public $class_row = '\\test\\Row_fruit';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_name() { return (new \aeqdev\APDO\Schema\Column\String())->length(20)->required(); }
    protected function column_color() { return (new \aeqdev\APDO\Schema\Column\String())->length(5); }
    protected function column_tree_id() { return (new \aeqdev\APDO\Schema\Column\Int())->fkey(); }
}

/**
 * @method \test\Table_fruit table()

 * @method \test\Row_tree tree()
 */
class Row_fruit extends \aeqdev\APDO\Schema\Row
{
    public $id;
    public $name;
    public $color;
    public $tree_id;

    /** @var \test\Row_tree */
    public $tree;
}

/**
 * @method \test\Row_tree[] fetchAll
 * @method \test\Row_tree[] fetchPage
 * @method \test\Row_tree fetchOne
 *
 * @method \test\Statement_tree log
 * @method \test\Statement_tree cache
 * @method \test\Statement_tree nothing
 * @method \test\Statement_tree table
 * @method \test\Statement_tree pkey
 * @method \test\Statement_tree fetchMode
 * @method \test\Statement_tree join
 * @method \test\Statement_tree leftJoin
 * @method \test\Statement_tree where
 * @method \test\Statement_tree orWhere
 * @method \test\Statement_tree key
 * @method \test\Statement_tree orKey
 * @method \test\Statement_tree groupBy
 * @method \test\Statement_tree having
 * @method \test\Statement_tree orderBy
 * @method \test\Statement_tree addOrderBy
 * @method \test\Statement_tree limit
 * @method \test\Statement_tree offset
 * @method \test\Statement_tree fields
 * @method \test\Statement_tree handler
 * @method \test\Statement_tree referrers
 * @method \test\Statement_tree references
 * @method \test\Statement_tree referrersUnique
 * @method \test\Statement_tree referencesUnique
 * @method \test\Statement_tree refs
 */
class Statement_tree extends \aeqdev\APDO\Schema\Statement {}

/**
 * @method \test\Row_tree_extra[] fetchAll
 * @method \test\Row_tree_extra[] fetchPage
 * @method \test\Row_tree_extra fetchOne
 *
 * @method \test\Statement_tree_extra log
 * @method \test\Statement_tree_extra cache
 * @method \test\Statement_tree_extra nothing
 * @method \test\Statement_tree_extra table
 * @method \test\Statement_tree_extra pkey
 * @method \test\Statement_tree_extra fetchMode
 * @method \test\Statement_tree_extra join
 * @method \test\Statement_tree_extra leftJoin
 * @method \test\Statement_tree_extra where
 * @method \test\Statement_tree_extra orWhere
 * @method \test\Statement_tree_extra key
 * @method \test\Statement_tree_extra orKey
 * @method \test\Statement_tree_extra groupBy
 * @method \test\Statement_tree_extra having
 * @method \test\Statement_tree_extra orderBy
 * @method \test\Statement_tree_extra addOrderBy
 * @method \test\Statement_tree_extra limit
 * @method \test\Statement_tree_extra offset
 * @method \test\Statement_tree_extra fields
 * @method \test\Statement_tree_extra handler
 * @method \test\Statement_tree_extra referrers
 * @method \test\Statement_tree_extra references
 * @method \test\Statement_tree_extra referrersUnique
 * @method \test\Statement_tree_extra referencesUnique
 * @method \test\Statement_tree_extra refs
 */
class Statement_tree_extra extends \aeqdev\APDO\Schema\Statement {}

/**
 * @method \test\Row_fruit[] fetchAll
 * @method \test\Row_fruit[] fetchPage
 * @method \test\Row_fruit fetchOne
 *
 * @method \test\Statement_fruit log
 * @method \test\Statement_fruit cache
 * @method \test\Statement_fruit nothing
 * @method \test\Statement_fruit table
 * @method \test\Statement_fruit pkey
 * @method \test\Statement_fruit fetchMode
 * @method \test\Statement_fruit join
 * @method \test\Statement_fruit leftJoin
 * @method \test\Statement_fruit where
 * @method \test\Statement_fruit orWhere
 * @method \test\Statement_fruit key
 * @method \test\Statement_fruit orKey
 * @method \test\Statement_fruit groupBy
 * @method \test\Statement_fruit having
 * @method \test\Statement_fruit orderBy
 * @method \test\Statement_fruit addOrderBy
 * @method \test\Statement_fruit limit
 * @method \test\Statement_fruit offset
 * @method \test\Statement_fruit fields
 * @method \test\Statement_fruit handler
 * @method \test\Statement_fruit referrers
 * @method \test\Statement_fruit references
 * @method \test\Statement_fruit referrersUnique
 * @method \test\Statement_fruit referencesUnique
 * @method \test\Statement_fruit refs
 */
class Statement_fruit extends \aeqdev\APDO\Schema\Statement {}

