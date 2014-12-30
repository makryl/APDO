<?php

namespace test\aeqdev\APDO\TestSchema\generated;

/**
 * @property \test\aeqdev\APDO\TestSchema\Table_tree $tree Tree
 * @property \test\aeqdev\APDO\TestSchema\Table_tree_extra $tree_extra Tree extra
 * @property \test\aeqdev\APDO\TestSchema\Table_fruit $fruit Fruit
 *
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree tree Tree
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_tree_extra tree_extra Tree extra
 * @method \test\aeqdev\APDO\TestSchema\generated\Statement_fruit fruit Fruit
 */
class TestSchema extends \aeqdev\APDO\Schema
{
    public $prefix = 'apdo_test_';

    public $class_tree = '\\test\\aeqdev\\APDO\\TestSchema\\Table_tree';
    public $class_tree_extra = '\\test\\aeqdev\\APDO\\TestSchema\\Table_tree_extra';
    public $class_fruit = '\\test\\aeqdev\\APDO\\TestSchema\\Table_fruit';
}
