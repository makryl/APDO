<?php

namespace aeqdev\APDO\Schema\Column;

use aeqdev\APDO\Schema\Column;

/**
 * Integer column.
 * Adds FILTER_VALIDATE_INT filter.
 */
class Int extends Column
{

    public function __construct()
    {
        $this->filter(FILTER_VALIDATE_INT);
    }

}
