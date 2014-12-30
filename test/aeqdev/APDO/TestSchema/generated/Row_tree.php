<?php

namespace test\aeqdev\APDO\TestSchema\generated;

/**
 * Tree
 *
 * @property \test\aeqdev\APDO\TestSchema\Table_tree $table
 *
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree_extra tree_extra__parent Tree extra (Parent tree)
 * @method \test\aeqdev\APDO\TestSchema\Row_tree_extra tree_extra__tree Tree extra (Tree)
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_fruit fruit Fruit
 */
class Row_tree extends \aeqdev\APDO\Schema\Row
{
    /** @var int */
    public $id;
    /** @var string Name */
    public $name;

    /** @var \test\aeqdev\APDO\TestSchema\Row_tree_extra[] Tree extra (Parent tree) */
    public $tree_extra__parent = [];
    /** @var \test\aeqdev\APDO\TestSchema\Row_tree_extra Tree extra (Tree) */
    public $tree_extra__tree;
    /** @var \test\aeqdev\APDO\TestSchema\Row_fruit[] Fruit */
    public $fruit = [];
}
