<?php

namespace test\aeqdev\APDO\TestSchema\generated;

/**
 * Tree extra
 *
 * @property \test\aeqdev\APDO\TestSchema\Table_tree_extra $table
 *
 * @method \test\aeqdev\APDO\TestSchema\Row_tree parent Parent tree
 * @method \test\aeqdev\APDO\TestSchema\Row_tree tree Tree
 */
class Row_tree_extra extends \aeqdev\APDO\Schema\Row
{
    /** @var int */
    public $id;
    /** @var int Height */
    public $height;
    /** @var int|\test\aeqdev\APDO\TestSchema\Row_tree Tree */
    public $tree;
    /** @var int|\test\aeqdev\APDO\TestSchema\Row_tree Parent tree */
    public $parent;
}
