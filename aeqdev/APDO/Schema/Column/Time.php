<?php

/*
 * http://aeqdev.com/tools/php/apdo/
 * v 0.2
 *
 * Copyright © 2013 Krylosov Maksim <Aequiternus@gmail.com>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

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
     * @return static|$this|\this Current column.
     */
    public function format($format)
    {
        $this->format = $format;
        return $this;
    }

}
