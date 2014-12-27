<?php

namespace aeqdev\APDO\Schema\Column;

/**
 * Time column.
 * Adds validator that converts value to date/time in specified format.
 * Format can be specified using $format property or format() function.
 * By default uses format 'c' (ISO 8601: 2004-02-12T15:19:21+00:00).
 * See http://us2.php.net/manual/function.date.php for details about format.
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
     * Sets date/time format.
     *
     * @return static|$this Current column.
     */
    public function format($format)
    {
        $this->format = $format;
        return $this;
    }

}
