<?php

namespace test\aeqdev\APDO\TestSchema\generated;

/**
 * Fruit
 *
 * @property \test\aeqdev\APDO\TestSchema\Table_fruit $table
 *
 * @method \test\aeqdev\APDO\TestSchema\Row_tree tree Tree
 */
class Row_fruit extends \aeqdev\APDO\Schema\Row
{
    /** @var int */
    public $id;
    /** @var string Name */
    public $name;
    /** @var string Color */
    public $color;
    /** @var int|\test\aeqdev\APDO\TestSchema\Row_tree Tree */
    public $tree;
}
