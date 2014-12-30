<?php

namespace aeqdev\APDO\Schema\Column;

use aeqdev\APDO\Schema\Column;

/**
 * Float column.
 * Adds FILTER_VALIDATE_FLOAT filter.
 */
class Float extends Column
{

    public function __construct()
    {
        $this->filter(FILTER_VALIDATE_FLOAT);
    }

}
