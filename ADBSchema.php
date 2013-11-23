<?php

/*
 * http://aeqdev.com/tools/php/apdo/
 * v 0.1
 *
 * Copyright © 2013 Krylosov Maksim <Aequiternus@gmail.com>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace aeqdev;

/**
 *
 */
class ADBSchema extends APDO
{

    public $prefix;

    protected $tables = [];

    public function __get($name)
    {
        if (!isset($this->tables[$name])) {
            $classTable = $this->{'class_' . $name};
            $this->tables[$name] = new $classTable($this);
        }
        return $this->tables[$name];
    }

    public function __call($name, $args)
    {
        return $this->{$name}->statement();
    }

    public function statement($statement = null, $args = null)
    {
        return new ADBSchemaStatement($this->parameters, $statement, $args);
    }

}

/**
 *
 */
class ADBSchemaTable
{

    /**
     * @var \aeqdev\ADBSchema
     */
    public $schema;

    public $name;
    public $pkey;
    public $ukey;
    public $fkey;
    public $cols;

    protected $class_row;

    public function __construct(ADBSchema $schema)
    {
        $this->schema = $schema;
    }

    public function statement() {
        return $this->schema->statement()
            ->schemaTable($this)
            ->fetchMode(\PDO::FETCH_CLASS, $this->class_row, [$this]);
    }

    public function create()
    {
        $classRow = $this->class_row;
        return new $classRow($this, true);
    }

    public function get($pkey)
    {
        return $this->statement()
            ->key($pkey)
            ->fetchOne();
    }

}

/**
 *
 */
class ADBSchemaStatement extends APDOStatement
{

    protected $schemaTable;

    public function schemaTable(ADBSchemaTable $table)
    {
        $this->schemaTable = $table;
        return $this->table($table->schema->prefix . $table->name)
            ->pkey($table->pkey);
    }

    public function refs(&$data)
    {
        if (empty($data)) {
            return $this->nothing();
        }

        $itemTable = is_array($data)
            ? reset($data)->table()
            : $data->table();

        if (isset($itemTable->fkey[$this->schemaTable->name])) {
            return $this->referrers(
                $data,
                $itemTable->name,
                $this->schemaTable->name,
                $itemTable->fkey[$this->schemaTable->name],
                $itemTable->pkey,
                isset($itemTable->ukey[$itemTable->fkey[$this->schemaTable->name]])
            );
        } else if (isset($this->schemaTable->fkey[$itemTable->name])) {
            return $this->references(
                $data,
                $this->schemaTable->name,
                $itemTable->name,
                $this->schemaTable->fkey[$itemTable->name],
                $itemTable->pkey,
                isset($this->schemaTable->ukey[$this->schemaTable->fkey[$itemTable->name]])
            );
        }
    }

}

/**
 *
 */
abstract class ADBSchemaRow
{

    protected $table;
    protected $new;
    protected $cols;
    protected $refs;

    public function table()
    {
        return $this->table;
    }

    public function __construct(ADBSchemaTable $table, $new = false)
    {
        $this->table = $table;
        $this->new = $new;
    }

    public function __call($name, $args)
    {
        /* @var $statement \aeqdev\ADBSchemaStatement */
        $statement = $this->table->schema->{$name}()->refs($this);
        /* @var $refTable \aeqdev\ADBSchemaTable */
        $refTable = $this->table->schema->{$name};

        return isset($refTable->fkey[$this->table->name])
            && !isset($refTable->ukey[$refTable->fkey[$this->table->name]])
            ? $statement->fetchAll()
            : $statement->fetchOne();
    }

    public function save()
    {
        if ($this->new) {
            $pkey = $this->table->statement()
                ->insert($this->values());
            if (isset($pkey)) {
                $this->{$this->table->pkey} = $pkey;
            }
            $this->new = false;
        } else {
            $this->table->statement()
                ->key($this->pkey())
                ->update($this->values());
        }
    }

    public function pkey()
    {
        if (is_array($this->table->pkey)) {
            $pkey = [];
            foreach ($this->table->pkey as $field) {
                $pkey []= $this->{$field};
            }
            return $pkey;
        } else {
            return $this->{$this->table->pkey};
        }
    }

    public function values()
    {
        $this->validValues = [];
        $exceptions = [];
        foreach ($this->table->cols as $name) {
            try {
                $this->validValues[$name] = $this->table->{'valid_' . $name}($this);
            } catch (ADBSchemaValidatorSkipException $e) {
                continue;
            } catch (\Exception $e) {
                $exceptions[$name] = $e;
                break;
            }
        }
        if (!empty($exceptions)) {
            throw new ADBSchemaValidatorException($exceptions);
        }
        return $this->validValues;
    }

}

class ADBSchemaValidator
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
            throw new ADBSchemaValidatorSkipException();
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

class ADBSchemaValidatorSkipException extends \Exception {}

class ADBSchemaValidatorException extends \Exception
{

    public $exceptions;

    public function __construct($exceptions, $message = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->exceptions = $exceptions;
    }

}
