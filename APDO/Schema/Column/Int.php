<?php

namespace aeqdev\APDO\Schema\Column;

/**
 *
 */
class Int extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->filter(FILTER_VALIDATE_INT);
    }

}
