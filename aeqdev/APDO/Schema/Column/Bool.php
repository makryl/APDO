<?php

namespace aeqdev\APDO\Schema\Column;

/**
 * Boolean column.
 * Adds FILTER_VALIDATE_BOOLEAN filter.
 */
class Bool extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->filter(FILTER_VALIDATE_BOOLEAN);
    }

}
