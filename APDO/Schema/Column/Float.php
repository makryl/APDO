<?php

namespace aeqdev\APDO\Schema\Column;

/**
 *
 */
class Float extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->filter(FILTER_VALIDATE_FLOAT);
    }

}