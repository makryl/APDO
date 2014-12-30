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
