<?php

namespace aeqdev\APDO;

/**
 * Memcache for APDO.
 * Prevents sending identical queries to database.
 * Allows reusing retrieved rows for references (reduces queries).
 * For clearing, increments version number, that used as prefix in key names.
 */
class Memcache extends \Memcache implements ICache
{

    protected $prefix;
    protected $prefixInit;
    protected $version;
    protected $versionKey;

    public function get($name)
    {
        $r = parent::get($this->prefix($name));
        return $r === false ? null : $r;
    }

    public function set($name, $value, $compress = null, $ttl = null)
    {
        return parent::set($this->prefix($name), $value, $compress, $ttl);
    }

    public function clear()
    {
        $this->prefixInit();
        $this->version = parent::increment($this->versionKey);
        $this->prefix = $this->prefixInit . '/' . $this->version . '/';
    }

    /**
     * Sets prefix for key names.
     * Recomended prefix: "domain.name/apdo".
     *
     * @param string $prefix
     */
    public function setPrefix($prefix = null)
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
        if (!isset($this->prefix)) {
            $this->version = time();
            $this->versionKey = $this->prefixInit . '/version';
            if (!parent::add($this->versionKey, $this->version)) {
                $this->version = parent::get($this->versionKey);
            }
            $this->prefix = $this->prefixInit . '/' . $this->version . '/';
        }
    }

}
