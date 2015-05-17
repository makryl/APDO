<?php

namespace aeqdev\APDO\Schema\Column;

use aeqdev\APDO\Schema\Column;
use aeqdev\APDO\Schema\Table;

/**
 * Boolean column.
 * Adds FILTER_VALIDATE_BOOLEAN filter.
 */
class Bool extends Column
{

    public function __construct(Table $table, $name)
    {
        parent::__construct($table, $name);
        $this->filterVar(FILTER_VALIDATE_BOOLEAN, [
            'options' => [
                'default' => false
            ],
            'flags' => FILTER_NULL_ON_FAILURE,
        ]);
    }

}
