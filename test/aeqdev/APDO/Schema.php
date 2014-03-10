<?php

namespace test;

/**
 * @property \test\Table_tree $tree
 * @property \test\Table_tree_extra $tree_extra
 * @property \test\Table_fruit $fruit
 *
 * @method \test\Statement_tree tree
 * @method \test\Statement_tree_extra tree_extra
 * @method \test\Statement_fruit fruit
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
 * @method \test\Row_tree create
 * @method \test\Row_tree get
 *
 * @method \aeqdev\APDO\Schema\Column\Int id
 * @method \aeqdev\APDO\Schema\Column\String name
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
 * @property \test\Table_tree $table
 *
 * @method \test\Row_tree_extra tree_extra
 * @method \test\Row_fruit[] fruit
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
 * @method \test\Row_tree_extra create
 * @method \test\Row_tree_extra get
 *
 * @method \aeqdev\APDO\Schema\Column\Int id
 * @method \aeqdev\APDO\Schema\Column\Int height
 * @method \aeqdev\APDO\Schema\Column\Int tree_id
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
 * @property \test\Table_tree_extra $table
 *
 * @method \test\Row_tree tree
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
 * @method \test\Row_fruit create
 * @method \test\Row_fruit get
 *
 * @method \aeqdev\APDO\Schema\Column\Int id
 * @method \aeqdev\APDO\Schema\Column\String name
 * @method \aeqdev\APDO\Schema\Column\String color
 * @method \aeqdev\APDO\Schema\Column\Int tree_id
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
 * @property \test\Table_fruit $table
 *
 * @method \test\Row_tree tree
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
 * @method \test\Statement_tree_extra tree_extra
 * @method \test\Statement_fruit[] fruit
 */
class Statement_tree extends \aeqdev\APDO\Schema\Statement {}

/**
 * @method \test\Row_tree_extra[] fetchAll
 * @method \test\Row_tree_extra[] fetchPage
 * @method \test\Row_tree_extra fetchOne
 *
 * @method \test\Statement_tree tree
 */
class Statement_tree_extra extends \aeqdev\APDO\Schema\Statement {}

/**
 * @method \test\Row_fruit[] fetchAll
 * @method \test\Row_fruit[] fetchPage
 * @method \test\Row_fruit fetchOne
 *
 * @method \test\Statement_tree tree
 */
class Statement_fruit extends \aeqdev\APDO\Schema\Statement {}

