<?php

/*
 * http://aeqdev.com/tools/php/apdo/
 * v 1.0 | 20131102
 *
 * Copyright © 2013 Krylosov Maksim <Aequiternus@gmail.com>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace aeqdev\APDO;

interface ICache
{
    public function get($name);
    public function set($name, $value);
    public function clear();
}
