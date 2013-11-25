<?php

namespace aeqdev\APDO\Schema\Column;

/**
 *
 */
class Time extends \aeqdev\APDO\Schema\Column
{

    public $format = 'c';

    public function __construct()
    {
        $this->addValidator(function($value) {
            $value = strtotime($value);
            return $value === false ? null : date($this->format, $value);
        });
    }

    /**
     * @return \static
     */
    public function format($format)
    {
        $this->format = $format;
        return $this;
    }

}
