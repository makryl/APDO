<?php

namespace aeqdev\APDO\Schema\Column;

/**
 *
 */
class String extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        $this->addValidator(function($value) {
            if (isset($value)) {
                $value = trim($value);
            }
            return $value;
        });
    }

    /**
     * @return \static
     */
    public function length($length)
    {
        $this->addValidator(function($value) use ($length) {
            if (isset($value)) {
                $value = mb_substr($value, 0, $length);
            }
            return $value;
        });
    }

    /**
     * @return \static
     */
    public function sprintf($format)
    {

        return $this->addValidator(function($value) use ($format) {
            return sprintf($format, $value);
        });
    }

    /**
     * @return \static
     */
    public function stripTags($allowable_tags = null)
    {
        return $this->addValidator(function($value) use ($allowable_tags) {
            return strip_tags($value, $allowable_tags);
        });
    }

    public static $simple_tags_allowable = '<a><p><strong><em><ul><li><br><br/><br />';

    /**
     * @return \static
     */
    public function simpleTags()
    {
        return $this->stripTags(self::$simple_tags_allowable);
    }

    /**
     * @return \static
     */
    public function emptyNull()
    {
        return $this->addValidator(function($value) {
            return empty($value) ? null : $value;
        });
    }

    /**
     * @return \static
     */
    public function hash($algo, $raw = false)
    {
        return $this->addValidator(function($value) use ($algo, $raw) {
            return hash($algo, $value, $raw);
        });
    }

    /**
     * @return \static
     */
    public function match($pattern, $errorMessage = null)
    {
        return $this->filterVar(FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $pattern]], $errorMessage);
    }

    /**
     * @return \static
     */
    public function email($errorMessage = null)
    {
        return $this->filterVar(FILTER_VALIDATE_EMAIL, null, $errorMessage);
    }

    /**
     * @return \static
     */
    public function ip($options = null, $errorMessage = null)
    {
        return $this->filterVar(FILTER_VALIDATE_IP, $options, $errorMessage);
    }



    /**
     * @return \static
     */
    public function phone($errorMessage = null)
    {
        return $this->match('/^[\d\s\+\-\,\(\)]*$/', $errorMessage);
    }

}
