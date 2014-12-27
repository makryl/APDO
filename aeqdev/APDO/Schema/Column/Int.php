<?php

namespace aeqdev\APDO\Schema\Column;

/**
 * Integer column.
 * Adds FILTER_VALIDATE_INT filter.
 */
class Int extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->filter(FILTER_VALIDATE_INT);
    }

}
