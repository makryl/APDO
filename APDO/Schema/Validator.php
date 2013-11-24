<?php

namespace aeqdev\APDO\Schema;

class Validator
{

    public static function int($value)
    {
        return isset($value) && trim($value) !== '' ? (int)$value : null;
    }

    public static function float($value)
    {
        return isset($value) && trim($value) !== '' ? (float)$value : null;
    }

    public static function bool($value)
    {
        return isset($value) && trim($value) !== '' ? (bool)$value : null;
    }

    public static function string($value)
    {
        return trim($value);
    }

    public static function length($value, $length)
    {
        return mb_substr($value, 0, $length);
    }

    public static function datef($value, $format)
    {
        $value = strtotime($value);
        return $value === false ? null : date($format, $value);
    }

    public static function time($value)
    {
        return self::datef($value, 'c');
    }

    public static function date($value)
    {
        return self::datef($value, 'Y-m-d');
    }

    public static function emptyskip($value)
    {
        if (empty($value)) {
            throw new ColumnSkipException();
        }
        return $value;
    }

    public static function required($value, $errorMessage = null)
    {
        if (
            empty($value)
            && $value !== 0
            && $value !== 0.
            && $value !== false
        ) {
            throw new \Exception(isset($errorMessage) ? $errorMessage : _('Value required'));
        }
        return $value;
    }

}
