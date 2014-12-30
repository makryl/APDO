<?php

namespace test\aeqdev\APDO\TestSchema\generated;

/**
 * Fruit
 *
 * @property \test\aeqdev\APDO\TestSchema $schema
 *
 * @method \test\aeqdev\APDO\TestSchema\Row_fruit create
 * @method \test\aeqdev\APDO\TestSchema\Row_fruit get
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

    public $class_row = '\\test\\aeqdev\\APDO\\TestSchema\\Row_fruit';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_name() { return (new \aeqdev\APDO\Schema\Column\String())->length(20)->required()->comment('Name'); }
    protected function column_color() { return (new \aeqdev\APDO\Schema\Column\String())->length(5)->comment('Color'); }
    protected function column_tree() { return (new \aeqdev\APDO\Schema\Column\Int())->fkey()->comment('Tree'); }
}

/**
 * Fruit
 *
 * @method \test\aeqdev\APDO\TestSchema\generated\Result_fruit fetchAll
 * @method \test\aeqdev\APDO\TestSchema\generated\Result_fruit fetchPage
 * @method \test\aeqdev\APDO\TestSchema\Row_fruit fetchOne
 *
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree tree Tree
 */
class Statement_fruit extends \aeqdev\APDO\Schema\Statement {}

/**
 * Fruit
 *
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree tree Tree
 */
class Result_fruit extends \aeqdev\APDO\Schema\Result {}
