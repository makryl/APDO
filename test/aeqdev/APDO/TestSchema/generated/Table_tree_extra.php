<?php

namespace test\aeqdev\APDO\TestSchema\generated;

/**
 * Tree extra
 *
 * @property \test\aeqdev\APDO\TestSchema $schema
 *
 * @method \test\aeqdev\APDO\TestSchema\Row_tree_extra create
 * @method \test\aeqdev\APDO\TestSchema\Row_tree_extra get
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

    public $class_row = '\\test\\aeqdev\\APDO\\TestSchema\\Row_tree_extra';

    protected function column_id() { return (new \aeqdev\APDO\Schema\Column\Int()); }
    protected function column_height() { return (new \aeqdev\APDO\Schema\Column\Int())->comment('Height'); }
    protected function column_tree() { return (new \aeqdev\APDO\Schema\Column\Int())->fkey()->required()->comment('Tree'); }
    protected function column_parent() { return (new \aeqdev\APDO\Schema\Column\Int())->fkey()->comment('Parent tree'); }
}
