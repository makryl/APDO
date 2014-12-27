<?php

namespace aeqdev\APDO\Schema\Column;

/**
 * Float column.
 * Adds FILTER_VALIDATE_FLOAT filter.
 */
class Float extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->filter(FILTER_VALIDATE_FLOAT);
    }

}
