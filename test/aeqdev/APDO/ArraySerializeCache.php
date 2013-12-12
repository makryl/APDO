<?php

namespace aeqdev\APDO;

class ArraySerializeCache implements ICache
{

    protected $cache = [];

    public function clear()
    {
        $this->cache = [];
    }

    public function get($name)
    {
        return isset($this->cache[$name]) ? unserialize($this->cache[$name]) : null;
    }

    public function set($name, $value)
    {
        $this->cache[$name] = serialize($value);
    }

}
