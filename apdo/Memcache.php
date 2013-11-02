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

namespace aeqdev\apdo;



/**
 * Memcache for APDO.
 * Prevents sending identical queries to database.
 * Allows reusing retrieved rows for references (reduces queries).
 */
class Memcache extends \Memcache implements \aeqdev\IAPDOCache
{

    protected $prefix;
    protected $prefixInit;
    protected $version;
    protected $versionKey;



    function get($name)
    {
        $r = parent::get($this->prefix($name));
        return $r === false ? null : $r;
    }



    function set($name, $value, $compress = null, $ttl = null)
    {
        return parent::set($this->prefix($name), $value, $compress, $ttl);
    }



    function clear()
    {
        $this->prefixInit();
        $this->version = parent::increment($this->versionKey);
        $this->prefix = $this->prefixInit . '/' . $this->version . '/';
    }



    function setPrefix($prefix = null)
    {
        $this->prefixInit = $prefix;
        $this->prefix = null;
    }



    private function prefix($name)
    {
        $this->prefixInit();
        return $this->prefix . $name;
    }



    private function prefixInit()
    {
        if (!isset($this->prefix))
        {
            $this->version = time();
            $this->versionKey = $this->prefixInit . '/version';
            if (!parent::add($this->versionKey, $this->version))
            {
                $this->version = parent::get($this->versionKey);
            }

            $this->prefix = $this->prefixInit . '/' . $this->version . '/';
        }
    }

}
