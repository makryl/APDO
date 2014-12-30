<?php

namespace test\aeqdev\APDO;

/**
 * @property \test\aeqdev\APDO\Table_tree $tree Tree
 * @property \test\aeqdev\APDO\Table_tree_extra $tree_extra Tree extra
 * @property \test\aeqdev\APDO\Table_fruit $fruit Fruit
 *
 * @method \test\aeqdev\APDO\Statement_tree tree Tree
 * @method \test\aeqdev\APDO\Statement_tree_extra tree_extra Tree extra
 * @method \test\aeqdev\APDO\Statement_fruit fruit Fruit
 */
class Schema extends \aeqdev\APDO\Schema
{
    public $prefix = 'apdo_test_';

    public $class_tree = '\\test\\aeqdev\\APDO\\Table_tree';
    public $class_tree_extra = '\\test\\aeqdev\\APDO\\Table_tree_extra';
    public $class_fruit = '\\test\\aeqdev\\APDO\\Table_fruit';
}

/**
 * Tree
 *
 * @property \test\aeqdev\APDO\Schema $schema
 *
 * @method \test\aeqdev\APDO\Row_tree create
 * @method \test\aeqdev\APDO\Row_tree get
 *
 * @method \aeqdev\APDO\Schema\Column\Int id
 * @method \aeqdev\APDO\Schema\Column\String name Name
 */
class Table_tree extends \aeqdev\APDO\Schema\Table
{
    public $name = 'tree';
    public $comment = 'Tree';
    public $pkey = 'id';
    public $cols = [
        'id' => 'id',
        'name' => 'name',
    ];

    public $class_row = '\\test\\aeqdev\\APDO\\Row_tree';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_name() { return (new \aeqdev\APDO\Schema\Column\String())->length(20)->comment('Name'); }
}

/**
 * Tree
 *
 * @property \test\aeqdev\APDO\Table_tree $table
 *
 * @method \test\aeqdev\APDO\Statement_tree_extra tree_extra__parent Tree extra (Parent tree)
 * @method \test\aeqdev\APDO\Row_tree_extra tree_extra__tree Tree extra (Tree)
 * @method \test\aeqdev\APDO\Statement_fruit fruit Fruit
 */
class Row_tree extends \aeqdev\APDO\Schema\Row
{
    /** @var int */
    public $id;
    /** @var string Name */
    public $name;

    /** @var \test\aeqdev\APDO\Row_tree_extra[] Tree extra (Parent tree) */
    public $tree_extra__parent = [];
    /** @var \test\aeqdev\APDO\Row_tree_extra Tree extra (Tree) */
    public $tree_extra__tree;
    /** @var \test\aeqdev\APDO\Row_fruit[] Fruit */
    public $fruit = [];
}

/**
 * Tree
 *
 * @method \test\aeqdev\APDO\Result_tree fetchAll
 * @method \test\aeqdev\APDO\Result_tree fetchPage
 * @method \test\aeqdev\APDO\Row_tree fetchOne
 *
 * @method \test\aeqdev\APDO\Statement_tree_extra tree_extra__parent Tree extra (Parent tree)
 * @method \test\aeqdev\APDO\Statement_tree_extra tree_extra__tree Tree extra (Tree)
 * @method \test\aeqdev\APDO\Statement_fruit fruit Fruit
 */
class Statement_tree extends \aeqdev\APDO\Schema\Statement {}

/**
 * Tree
 *
 * @method \test\aeqdev\APDO\Statement_tree_extra tree_extra__parent Tree extra (Parent tree)
 * @method \test\aeqdev\APDO\Statement_tree_extra tree_extra__tree Tree extra (Tree)
 * @method \test\aeqdev\APDO\Statement_fruit fruit Fruit
 */
class Result_tree extends \aeqdev\APDO\Schema\Result {}

/**
 * Tree extra
 *
 * @property \test\aeqdev\APDO\Schema $schema
 *
 * @method \test\aeqdev\APDO\Row_tree_extra create
 * @method \test\aeqdev\APDO\Row_tree_extra get
 *
 * @method \aeqdev\APDO\Schema\Column\Int id
 * @method \aeqdev\APDO\Schema\Column\Int height Height
 * @method \aeqdev\APDO\Schema\Column\Int tree Tree
 * @method \aeqdev\APDO\Schema\Column\Int parent Parent tree
 */
class Table_tree_extra extends \aeqdev\APDO\Schema\Table
{
    public $name = 'tree_extra';
    public $comment = 'Tree extra';
    public $pkey = 'id';
    public $ukey = [
        'tree' => 'tree',
    ];
    public $fkey = [
        'parent' => 'tree',
        'tree' => 'tree',
    ];
    public $rkey = [
        'tree' => [
            'parent' => 'parent',
            'tree' => 'tree',
        ],
    ];
    public $cols = [
        'id' => 'id',
        'height' => 'height',
        'tree' => 'tree',
        'parent' => 'parent',
    ];

    public $class_row = '\\test\\aeqdev\\APDO\\Row_tree_extra';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_height() { return (new \aeqdev\APDO\Schema\Column\Int())->comment('Height'); }
    protected function column_tree() { return (new \aeqdev\APDO\Schema\Column\Int())->fkey()->required()->comment('Tree'); }
    protected function column_parent() { return (new \aeqdev\APDO\Schema\Column\Int())->fkey()->comment('Parent tree'); }
}

/**
 * Tree extra
 *
 * @property \test\aeqdev\APDO\Table_tree_extra $table
 *
 * @method \test\aeqdev\APDO\Row_tree parent Parent tree
 * @method \test\aeqdev\APDO\Row_tree tree Tree
 */
class Row_tree_extra extends \aeqdev\APDO\Schema\Row
{
    /** @var int */
    public $id;
    /** @var int Height */
    public $height;
    /** @var int|\test\aeqdev\APDO\Row_tree Tree */
    public $tree;
    /** @var int|\test\aeqdev\APDO\Row_tree Parent tree */
    public $parent;
}

/**
 * Tree extra
 *
 * @method \test\aeqdev\APDO\Result_tree_extra fetchAll
 * @method \test\aeqdev\APDO\Result_tree_extra fetchPage
 * @method \test\aeqdev\APDO\Row_tree_extra fetchOne
 *
 * @method \test\aeqdev\APDO\Statement_tree parent Parent tree
 * @method \test\aeqdev\APDO\Statement_tree tree Tree
 */
class Statement_tree_extra extends \aeqdev\APDO\Schema\Statement {}

/**
 * Tree extra
 *
 * @method \test\aeqdev\APDO\Statement_tree parent Parent tree
 * @method \test\aeqdev\APDO\Statement_tree tree Tree
 */
class Result_tree_extra extends \aeqdev\APDO\Schema\Result {}

/**
 * Fruit
 *
 * @property \test\aeqdev\APDO\Schema $schema
 *
 * @method \test\aeqdev\APDO\Row_fruit create
 * @method \test\aeqdev\APDO\Row_fruit get
 *
 * @method \aeqdev\APDO\Schema\Column\Int id
 * @method \aeqdev\APDO\Schema\Column\String name Name
 * @method \aeqdev\APDO\Schema\Column\String color Color
 * @method \aeqdev\APDO\Schema\Column\Int tree Tree
 */
class Table_fruit extends \aeqdev\APDO\Schema\Table
{
    public $name = 'fruit';
    public $comment = 'Fruit';
    public $pkey = 'id';
    public $fkey = [
        'tree' => 'tree',
    ];
    public $rkey = [
        'tree' => 'tree',
    ];
    public $cols = [
        'id' => 'id',
        'name' => 'name',
        'color' => 'color',
        'tree' => 'tree',
    ];

    public $class_row = '\\test\\aeqdev\\APDO\\Row_fruit';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_name() { return (new \aeqdev\APDO\Schema\Column\String())->length(20)->required()->comment('Name'); }
    protected function column_color() { return (new \aeqdev\APDO\Schema\Column\String())->length(5)->comment('Color'); }
    protected function column_tree() { return (new \aeqdev\APDO\Schema\Column\Int())->fkey()->comment('Tree'); }
}

/**
 * Fruit
 *
 * @property \test\aeqdev\APDO\Table_fruit $table
 *
 * @method \test\aeqdev\APDO\Row_tree tree Tree
 */
class Row_fruit extends \aeqdev\APDO\Schema\Row
{
    /** @var int */
    public $id;
    /** @var string Name */
    public $name;
    /** @var string Color */
    public $color;
    /** @var int|\test\aeqdev\APDO\Row_tree Tree */
    public $tree;
}

/**
 * Fruit
 *
 * @method \test\aeqdev\APDO\Result_fruit fetchAll
 * @method \test\aeqdev\APDO\Result_fruit fetchPage
 * @method \test\aeqdev\APDO\Row_fruit fetchOne
 *
 * @method \test\aeqdev\APDO\Statement_tree tree Tree
 */
class Statement_fruit extends \aeqdev\APDO\Schema\Statement {}

/**
 * Fruit
 *
 * @method \test\aeqdev\APDO\Statement_tree tree Tree
 */
class Result_fruit extends \aeqdev\APDO\Schema\Result {}
