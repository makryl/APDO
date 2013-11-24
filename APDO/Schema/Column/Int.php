<?php

namespace aeqdev\APDO\Schema\Column;

/**
 *
 */
class Int extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->addValidator(function($value) {
            return isset($value) && trim($value) !== '' ? (int)$value : null;
        });
    }

}
