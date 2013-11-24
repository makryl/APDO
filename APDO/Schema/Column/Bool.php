<?php

namespace aeqdev\APDO\Schema\Column;

/**
 *
 */
class Bool extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->addValidator(function($value) {
            return isset($value) && trim($value) !== '' ? (bool)$value : null;
        });
    }

}
