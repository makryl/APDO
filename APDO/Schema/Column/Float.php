<?php

namespace aeqdev\APDO\Schema\Column;

/**
 *
 */
class Float extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->addValidator(function($value) {
            return isset($value) && trim($value) !== '' ? (float)$value : null;
        });
    }

}
