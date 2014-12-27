<?php

namespace aeqdev\APDO;

interface ICache
{
    public function get($name);
    public function set($name, $value);
    public function clear();
}
