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
