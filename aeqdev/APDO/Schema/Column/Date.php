<?php

namespace aeqdev\APDO\Schema\Column;

/**
 * Date column.
 * Adds validator that converts value to date/time in specified format.
 * Format can be specified using $format property or format() function.
 * By default uses format 'Y-m-d' (2004-02-12).
 * See http://us2.php.net/manual/function.date.php for details about format.
 */
class Date extends Time
{

    public $format = 'Y-m-d';

}
