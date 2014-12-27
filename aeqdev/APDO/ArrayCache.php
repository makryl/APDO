<?php

namespace aeqdev\APDO;

/**
 * Simple array cache for APDO.
 * Prevents sending identical queries to database.
 * Allows reusing retrieved rows for references (reduces queries).
 * Works only within one script run.
 */
class ArrayCache implements ICache
{

    protected $cache = [];

    public function clear()
    {
        $this->cache = [];
    }

    public function get($name)
    {
        return isset($this->cache[$name]) ? $this->cache[$name] : null;
    }

    public function set($name, $value)
    {
        $this->cache[$name] = $value;
    }

}
