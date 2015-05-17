<?php

namespace aeqdev\APDO\Schema\Column;

use aeqdev\APDO\Schema\Column;
use aeqdev\APDO\Schema\Table;

/**
 * Integer column.
 * Adds FILTER_VALIDATE_INT filter.
 */
class Int extends Column
{

    public function __construct(Table $table, $name)
    {
        parent::__construct($table, $name);
        $this->filterVar(FILTER_VALIDATE_INT);
    }

}
