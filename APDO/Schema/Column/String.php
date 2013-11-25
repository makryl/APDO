<?php

namespace aeqdev\APDO\Schema\Column;

/**
 *
 */
class String extends \aeqdev\APDO\Schema\Column
{

    public function __construct()
    {
        return $this->addValidator(function($value) {
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
        return $this->addValidator(function($value) use ($length) {
            if (isset($value)) {
                $value = mb_substr($value, 0, $length);
            }
            return $value;
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
    public function email($error_message = null)
    {
        return $this->filterStrict(FILTER_VALIDATE_EMAIL, null, $error_message);
    }

    /**
     * @return \static
     */
    public function ip($error_message = null)
    {
        return $this->filterStrict(FILTER_VALIDATE_IP, null, $error_message);
    }

    /**
     * @return \static
     */
    public function match($pattern, $error_message = null)
    {
        return $this->filterStrict(FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $pattern]], $error_message);
    }

    /**
     * @return \static
     */
    public function phone($errorMessage = null)
    {
        return $this->match('/^[\d\s\+\-\,\(\)]*$/', $errorMessage);
    }

}
