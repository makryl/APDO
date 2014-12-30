<?php

namespace test\aeqdev\APDO\TestSchema\generated;

/**
 * Tree
 *
 * @property \test\aeqdev\APDO\TestSchema $schema
 *
 * @method \test\aeqdev\APDO\TestSchema\Row_tree create
 * @method \test\aeqdev\APDO\TestSchema\Row_tree get
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

    public $class_row = '\\test\\aeqdev\\APDO\\TestSchema\\Row_tree';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_name() { return (new \aeqdev\APDO\Schema\Column\String())->length(20)->comment('Name'); }
}

/**
 * Tree
 *
 * @method \test\aeqdev\APDO\TestSchema\generated\Result_tree fetchAll
 * @method \test\aeqdev\APDO\TestSchema\generated\Result_tree fetchPage
 * @method \test\aeqdev\APDO\TestSchema\Row_tree fetchOne
 *
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree_extra tree_extra__parent Tree extra (Parent tree)
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree_extra tree_extra__tree Tree extra (Tree)
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_fruit fruit Fruit
 */
class Statement_tree extends \aeqdev\APDO\Schema\Statement {}

/**
 * Tree
 *
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree_extra tree_extra__parent Tree extra (Parent tree)
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree_extra tree_extra__tree Tree extra (Tree)
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_fruit fruit Fruit
 */
class Result_tree extends \aeqdev\APDO\Schema\Result {}
