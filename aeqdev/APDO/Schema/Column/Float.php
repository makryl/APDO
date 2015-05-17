<?php

namespace aeqdev\APDO\Schema\Column;

use aeqdev\APDO\Schema\Column;
use aeqdev\APDO\Schema\Table;

/**
 * Float column.
 * Adds FILTER_VALIDATE_FLOAT filter.
 */
class Float extends Column
{

    public function __construct(Table $table, $name)
    {
        parent::__construct($table, $name);
        $this->filterVar(FILTER_VALIDATE_FLOAT);
    }

}
