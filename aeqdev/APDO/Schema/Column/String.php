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
 * String column.
 * Adds trim filter.
 * Has fiew string type additional filters.
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
     * Adds validator that reduses string length to specified value.
     * Uses mb_substr function with default encoding.
     * You can set default encoding using mb_internal_encoding function or php.ini.
     *
     * @return \static Current column.
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
     * Adds validator that strips all html tags.
     * Uses strip_tag function.
     * See http://us2.php.net/manual/function.strip-tags.php for details.
     *
     * @param string $allowable_tags You can use the optional second parameter to specify tags which should not be stripped.
     * @return \static Current column.
     */
    public function stripTags($allowable_tags = null)
    {
        return $this->addValidator(function($value) use ($allowable_tags) {
            return strip_tags($value, $allowable_tags);
        });
    }

    public static $simple_tags_allowable = '<a><p><strong><em><ul><li><br><br/><br />';

    /**
     * Same as stripTags validator with list of allowable tags, defined in $simple_tags_allowable static variable.
     * By default next tags allowed: &lt;a>&lt;p>&lt;strong>&lt;em>&lt;ul>&lt;li>&lt;br>
     *
     * @return \static Current column.
     */
    public function simpleTags()
    {
        return $this->stripTags(self::$simple_tags_allowable);
    }

    /**
     * Validator sets null for empty values.
     *
     * @return \static Current column.
     */
    public function emptyNull()
    {
        return $this->addValidator(function($value) {
            return empty($value) ? null : $value;
        });
    }

    /**
     * Adds hash filter.
     * See http://us2.php.net/manual/function.hash.php for details.
     *
     * @param string $algo Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)
     * @param bool $raw When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits.
     * @return \static Current column.
     */
    public function hash($algo, $raw = false)
    {
        return $this->addValidator(function($value) use ($algo, $raw) {
            return hash($algo, $value, $raw);
        });
    }

    /**
     * Adds FILTER_VALIDATE_EMAIL strict filter.
     *
     * @param string $error_message Error message on validation fail.
     * @return \static Current column.
     */
    public function email($error_message = null)
    {
        return $this->filterStrict(FILTER_VALIDATE_EMAIL, null, $error_message);
    }

    /**
     * Adds FILTER_VALIDATE_IP strict filter.
     *
     * @param string $error_message Error message on validation fail.
     * @return \static Current column.
     */
    public function ip($error_message = null)
    {
        return $this->filterStrict(FILTER_VALIDATE_IP, null, $error_message);
    }

    /**
     * Adds FILTER_VALIDATE_REGEXP strict filter.
     *
     * @param string $pattern Regular expression.
     * @param string $error_message Error message on validation fail.
     * @return \static Current column.
     */
    public function match($pattern, $error_message = null)
    {
        return $this->filterStrict(FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $pattern]], $error_message);
    }

    /**
     * Phone validator allows using only digits, spaces, commas, brackets and plus/minus signs.
     *
     * @param string $error_message Error message on validation fail.
     * @return \static Current column.
     */
    public function phone($errorMessage = null)
    {
        return $this->match('/^[\d\s\+\-\,\(\)]*$/', $errorMessage);
    }

}
