<?php

/*
 * http://aeqdev.com/tools/php/apdo/
 * v 0.4 | 20131102
 *
 * Copyright © 2013 Krylosov Maksim <Aequiternus@gmail.com>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace aeqdev;

use \PDO;
use apdo\ILog;
use apdo\ICache;



/**
 * Represents connection to database.
 *
 * Features:
 *  - Uses PDO for database access.
 *  - Lazy connection (established before first query sent to database).
 *  - Stores query log.
 *  - Caches statement results and rows if possible.
 *  - Simple interface to make queries and retrieve results.
 *  - Simple using foreign keys to retrieve referenced data.
 */
class APDO
{

    private $pdo;

    private $dsn;
    private $username;
    private $password;
    private $options;

    private $pkey = 'id';

    /**
     * @var ILog
     */
    private $log;

    /**
     * @var ICache
     */
    private $cache;

    private $statementCount;
    private $executedCount;
    private $cachedCount;

    /**
     * @var APDOStatement
     */
    private $last;



    /**
     * Stores connection parameters.
     * Connection DO NOT established at this point.
     * Connection establishes before first query sent to database
     * (cached queries do not sent to database), or by calling pdo() method.
     *
     * See description of arguments in PDO constructor documentation.
     *
     * @param string        $dsn
     * @param string        $username
     * @param string        $password
     * @param string        $options
     */
    function __construct($dsn, $username, $password, $options)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }



    /**
     * Returns PDO object.
     * At first call creates PDO (and establishes connection to database).
     *
     * @return \PDO                         Associated PDO object.
     */
    function pdo()
    {
        if (!isset($this->pdo))
        {
            $this->options = (array)$this->options + [
                PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES  => false,
            ];
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
            $this->executedCount = 0;
        }
        return $this->pdo;
    }



    /**
     * @return bool                         True if connection established, false otherwise.
     */
    function connected()
    {
        return isset($this->pdo);
    }



    /**
     * @return int                          Count of created statements.
     */
    function statementCount()
    {
        return $this->statementCount;
    }



    /**
     * @return int                          Count of queries sent to database.
     */
    function executedCount()
    {
        return $this->executedCount;
    }



    /**
     * @return int                          Count of cached queries (that queries was not sent to database).
     */
    function cachedCount()
    {
        return $this->cachedCount;
    }



    /**
     * @return \aeqdev\APDOStatement        Last exequted statement.
     */
    function last()
    {
        return $this->last;
    }



    /**
     * Sets default primary key name for new statements.
     *
     * @param string $pkey                  Primary key name.
     */
    function setPkey($pkey)
    {
        $this->pkey = $pkey;
    }



    /**
     * Sets or removes default logger of queries, sent to database, for new statements.
     * Logger must implements ILog interface with only one debug($msg) method.
     *
     * @param null|ILog         $log    Logger to set as default.
     */
    function setLog($log = null)
    {
        $this->log = $log;
    }



    /**
     * Sets or removes default cacher for new statements.
     * Cacher must implements ICache interface with three simple methods:
     * get($name), set($name, $value) and clear().
     *
     * @param null|ICache       $cache Cacher to set as default.
     */
    function setCache($cache = null)
    {
        $this->cache = $cache;
    }



    /**
     * Creates new statement.
     *
     * @param string        $statement      SQL statement.
     * @param string|array  $args           Argument or array of arguments for the statement.
     * @return \aeqdev\APDOStatement        Created statement.
     */
    function statement($statement = null, $args = null)
    {
        return (new APDOStatement(
            $this, $statement, $args,
            $this->statementCount, $this->executedCount, $this->cachedCount,
            $this->last
        ))
            ->pkey($this->pkey)
            ->log($this->log)
            ->cache($this->cache);
    }



    /**
     * Creates new statement and sets table name.
     *
     * @param string        $table          Table name.
     * @return \aeqdev\APDOStatement        Created statement.
     */
    function from($table)
    {
        return $this->statement()
            ->table($table);
    }



    /**
     * Creates new statement and sets table name.
     * This method is alias for from().
     *
     * @param string        $table          Table name.
     * @return \aeqdev\APDOStatement        Created statement.
     */
    function in($table)
    {
        return $this->from($table);
    }

}



class APDOStatement
{

    /**
     * @var APDO
     */
    private $apdo;

    /**
     * @var ILog
     */
    private $log;

    /**
     * @var ICache
     */
    private $cache;

    private $nothing = false;
    private $statement;
    private $table;
    private $pkey;
    private $where;
    private $groupby;
    private $having;
    private $orderby;
    private $limit;
    private $offset = 0;
    private $fields = '*';
    private $args = [];
    private $handlers = [];

    private $rowCount;
    private $lastQuery;
    private $last;

    private $executedCount;
    private $cachedCount;



    /**
     * Stores creator APDO object.
     * Sets SQL statement and it's arguments.
     *
     * @param \aeqdev\APDO  $apdo           APDO object to associate with.
     * @param string        $statement      SQL statement.
     * @param string|array  $args           Argument or array of arguments for the statement.
     */
    function __construct(
        APDO $apdo, $statement = null, $args = null,
        &$statementCount = null, &$executedCount = null, &$cachedCount = null,
        &$last = null
    ) {
        $this->apdo = $apdo;
        $this->statement = $statement;
        $this->args = isset($args) ? $args : [];

        ++$statementCount;
        $this->executedCount =& $executedCount;
        $this->cachedCount =& $cachedCount;
        $this->last =& $last;
    }



    /**
     * Adds statement and it's arguments to the log.
     *
     * @param string        $statement      SQL statement
     * @param string|array  $args           Argument or array of arguments for the statement.
     */
    private function logAdd($statement, $args = null)
    {
        if (isset($this->log))
        {
            $this->log->debug($statement);
            if (!empty($args))
            {
                $this->log->debug(print_r($args, true));
            }
        }
    }



    /**
     * Sets or removes logger of queries, sent to database, for the statement.
     * Logger must implements ILog interface with only one debug($msg) method.
     *
     * @param null|ILog         $log    Logger.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function log($log = null)
    {
        $this->log = $log;
        return $this;
    }



    /**
     * Sets or removes cacher for the statement.
     * Cacher must implements ICache interface with three simple methods:
     * get($name), set($name, $value) and clear().
     *
     * @param null|ICache       $cache  Cacher.
     * @return \aeqdev\APDOStatement        Current statement.
     */

    function cache($cache = null)
    {
        $this->cache = $cache;
        return $this;
    }



    /**
     * @return int                          Count of rows, affected by last query.
     */
    function rowCount()
    {
        return $this->rowCount;
    }



    /**
     * @return string                       Last exequted query.
     */
    function lastQuery()
    {
        return $this->lastQuery;
    }



    /**
     * After calling this method, result will always empty array,
     * and no query will sent to database.
     *
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function nothing()
    {
        $this->nothing = true;
        return $this;
    }



    /**
     * Sets table name for the statement.
     *
     * @param string        $table          Table name.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function table($table)
    {
        $this->table = $table;
        return $this;
    }



    /**
     * Sets prymary key name for the statement.
     *
     * @param string        $name           Primary key name, or array of names for complex primary key.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function pkey($name)
    {
        $this->pkey = $name;
        return $this;
    }



    /**
     * Adds an JOIN to FROM section of the statement.
     *
     * @param string        $table          Table name to join with.
     * @param string        $on             Join condition.
     * @param string|array  $args           Argument or array of arguments for join conditions.
     * @param string        $joinType       Join type definition.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function join($table, $on = null, $args = null, $joinType = '')
    {
        $this->table .= "\n$joinType JOIN " . $table;
        if (!empty($on))
        {
            $this->table .= ' ON (' . $on . ')';
            $this->args = array_merge($this->args, array_values((array)$args));
        }
        return $this;
    }



    /**
     * Adds an LEFT JOIN to FROM section of the statement.
     * Same as join() method with 'LEFT' join type.
     *
     * @param string        $table          Table name to join with.
     * @param string        $on             Join conditions.
     * @param string|array  $args           Argument or array of arguments for join conditions.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function leftJoin($table, $on = null, $args = null)
    {
        return $this->join($table, $on, $args, 'LEFT');
    }



    /**
     * Adds conditions to the statement.
     *
     * By default conditions appends to previously defined conditions with AND operator.
     * Set last argument append type to true, if you want to append conditions with OR operator.
     *
     * @param string        $where          Conditions.
     * @param string|array  $args           Argument or array of arguments for conditions.
     * @param bool          $or             Append type.
     *                                      False means append with AND operator,
     *                                      true means append with OR operator.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function where($where, $args = null, $or = false)
    {
        if (empty($this->where))
        {
            $this->where = $where;
        }
        else
        {
            $this->where
                = '(' . $this->where . ')'
                . ($or ? ' OR ' : ' AND ')
                . '(' . $where . ')';
        }
        $this->where;
        $this->args = array_merge($this->args, array_values((array)$args));
        return $this;
    }



    /**
     * Adds conditions to the statement.
     *
     * Conditions appends to previously defined conditions with OR operator.
     *
     * @param string        $where          Conditions.
     * @param string|array  $args           Argument or array of arguments for conditions.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function orWhere($where, $args = null)
    {
        return $this->where($where, $args, true);
    }



    /**
     * Adds an key-value condition to the statement.
     * If first argument is an array, then IN operator will be used,
     * otherwise '=' operator.
     * If key name is not specified, primary key name will be used.
     *
     * By default conditions appends to previously defined conditions with AND operator.
     * Set last argument append type to true, if you want to append conditions with OR operator.
     *
     * @param string|array  $args           Value or array of values.
     * @param string|array  $name           Field name or array of field names. By default primary key name is used.
     * @param bool          $or             Append type.
     *                                      False means append with AND operator,
     *                                      true means append with OR operator.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function key($args, $name = null, $or = false)
    {
        if (empty($name))
        {
            $name = $this->pkey;
        }
        if (is_array($name))
        {
            foreach ($name as $i => $n)
            {
                $this->key($args[$i], $n, $or);
            }
            return $this;
        }
        if (is_array($args))
        {
            $where = $name . ' IN (' . implode(',', array_fill(0, count($args), '?')) . ')';
        }
        else
        {
            $args = [$args];
            $where = $name . '=?';
        }
        return $this->where($where, $args, $or);
    }



    /**
     * Adds an key-value condition to the statement.
     * If first argument is an array, then IN operator will be used,
     * otherwise '=' operator.
     * If key name is not specified, primary key name will be used.
     *
     * Conditions appends to previously defined conditions with OR operator.
     *
     * @param string|array  $args           Value or array of values.
     * @param string|array  $name           Field name or array of field names. By default primary key name is used.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function orKey($args, $name = null)
    {
        return $this->key($args, $name, true);
    }



    /**
     * Sets GROUP BY section of the statement.
     *
     * @param string        $groupby        Group by definition.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function groupBy($groupby)
    {
        $this->groupby = $groupby;
        return $this;
    }



    /**
     * Sets HAVING section of the statement.
     *
     * @param string        $having         Having definition.
     * @param string|array  $args           Argument or array of arguments for having conditions.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function having($having, $args = null)
    {
        $this->having = $having;
        $this->args = array_merge($this->args, array_values((array)$args));
        return $this;
    }



    /**
     * Sets ORDER BY section of the statement.
     *
     * @param string        $orderby        Order by definition.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function orderBy($orderby)
    {
        $this->orderby = $orderby;
        return $this;
    }



    /**
     * Adds field with sort direction to ORDER BY section of the statement.
     *
     * @param string        $field          Field name.
     * @param bool          $desc           Default false for ASC, true for DESC.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function addOrderBy($field, $desc = false)
    {
        if (!empty($this->orderby))
        {
            $this->orderby .= ', ';
        }
        $this->orderby .= $field;
        if ($desc)
        {
            $this->orderby .= ' DESC';
        }
        return $this;
    }



    /**
     * Sets LIMIT section of the statement.
     *
     * @param int           $limit          Limit definition.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }



    /**
     * Sets OFFSET section of the statement.
     *
     * @param int           $offset         Offset definition.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }



    /**
     * Sets fields of the statement.
     * First argument can be string with fields list, or an array of filed names.
     *
     * @param string|array  $fields         Fields.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function fields($fields)
    {
        $this->fields = implode(', ', (array)$fields);
        return $this;
    }



    /**
     * Adds handler function, that will be executed on result.
     * Handler function should have result argument and return modified result.
     *
     * @param callback      $handler        Handler function for results.
     * @return \aeqdev\APDOStatement        Current statement.
     */
    function handler($handler)
    {
        $this->handlers [] = $handler;
        return $this;
    }



    /**
     * Executes handlers on result.
     *
     * @param array         $result         Result array.
     * @return array                        Result array.
     */
    private function handlers($result)
    {
        foreach ($this->handlers as $handler)
        {
            $result = $handler($result);
        }
        return $result;
    }



    /**
     * Executes SELECT query and returns it's result.
     *
     * Builds SQL using the statement.
     * Sends query to database or retrieves result from cache.
     * Call handlers on result array.
     *
     * Result retrieves from database using PDO's method fetchAll()
     *
     * @param int           $fetch_style    PDO fetch style.
     * @return array                        Result array.
     */
    function all($fetch_style = PDO::FETCH_ASSOC)
    {
        return $this->handlers(
            $this->nothing
                ? []
                : $this->query(
                    $this->statement ? : $this->buildSelect(),
                    $this->args,
                    empty($this->statement),
                    true,
                    $fetch_style
                )
        );
    }



    function buildSelect()
    {
        return 'SELECT ' . $this->fields
            . "\nFROM " . $this->table
            . (!empty($this->where)     ? "\nWHERE "    . $this->where      : '')
            . (!empty($this->groupby)   ? "\nGROUP BY " . $this->groupby    : '')
            . (!empty($this->having)    ? "\nHAVING "   . $this->having     : '')
            . (!empty($this->orderby)   ? "\nORDER BY " . $this->orderby    : '')
            . (!empty($this->limit)     ? "\nLIMIT "    . $this->limit      : '')
            . (!empty($this->offset)    ? "\nOFFSET "   . $this->offset     : '');
    }



    /**
     * Same as method all() with fetch style PDO::FETCH_KEY_PAIR.
     * Intended to retrieve key-value result array.
     *
     * @return array                        Result array.
     */
    function allK()
    {
        return $this->all(PDO::FETCH_KEY_PAIR);
    }



    /**
     * Same as method all() with limit 1, and returns first element of result array or null if result is empty.
     *
     * @param int           $fetch_style    PDO fetch style.
     * @return array                        Result array.
     */
    function one($fetch_style = PDO::FETCH_ASSOC)
    {
        $r = $this
            ->limit(1)
            ->all($fetch_style);
        return empty($r) ? null : $r[0];
    }



    /**
     * Same as method one() with fetch stype PDO::FETCH_NUM.
     * Intended to use with php language construct list().
     *
     * @return array                        Result array.
     */
    function oneL()
    {
        return $this->one(PDO::FETCH_NUM);
    }



    /**
     * Same as method all() with offset calculated from page number and current limit.
     *
     * @param int           $page           Page number.
     * @param int           $fetch_style    PDO fetch style.
     * @return array                        Result array.
     */
    function page($page = 1, $fetch_style = PDO::FETCH_ASSOC)
    {
        return $this
            ->offset($this->limit * (($page ? : 1) - 1))
            ->all($fetch_style);
    }



    /**
     * Same as method page() with fetch style PDO::FETCH_KEY_PAIR.
     * Intended to retrieve key-value result array.
     *
     * @param int           $page           Page number.
     * @return array                        Result array.
     */
    function pageK($page = 1)
    {
        return $this->page($page, PDO::FETCH_KEY_PAIR);
    }



    /**
     * Retrives array of specified field values only.
     * Keys and values of result array will be the same.
     * By default primary key name used.
     *
     * @param string        $name           Field name. Primary key name by default.
     * @return array                        Result array.
     */
    function keys($name = null)
    {
        if (empty($name))
        {
            $name = $this->pkey;
        }
        return $this
            ->fields([$name, $name])
            ->allK();
    }



    /**
     * Executes SELECT COUNT(*) query, using statement's table name and conditions, and returns count.
     * The query can be cached.
     *
     * @return int                          Retrived count.
     */
    function count()
    {
        $statement = "SELECT COUNT(*)\nFROM " . $this->table
            . (!empty($this->where) ? "\nWHERE " . $this->where : '');

        list($count) = $this->query($statement, $this->args, false, true, PDO::FETCH_NUM)[0];
        return $count;
    }



    /**
     * Executes statement.
     *
     * @return bool                         True on success or false on failure.
     */
    function execute()
    {
        return $this->query($this->statement, $this->args);
    }



    /**
     * Executes INSERT query using statement's table name,
     * and array of values with keys as field names.
     * Array of values also can be array of arrays for massive insertion.
     * Returns last inserted id.
     *
     * @param array         $values         Array of values or array of arrays of values to insert.
     * @return int                          Last inserted id.
     */
    function insert(array $values)
    {
        if (!isset($values[0]))
        {
            $values = [$values];
        }

        $names = array_keys($values[0]);
        $this->args = [];
        foreach ($values as $v)
        {
            foreach ($v as $a)
            {
                $this->args [] = $a;
            }
        }

        $this->query(
            'INSERT INTO ' . $this->table . ' ('
                . implode(',', $names) . ") VALUES\n("
                . implode(
                    "),\n(",
                    array_fill(0, count($values), implode(',', array_fill(0, count($names), '?')))
                ) . ')',
            $this->args
        );

        return $this->apdo->pdo()->lastInsertId();
    }



    /**
     * Executes UPDATE query using statement's table name, conditions,
     * and array of values with keys as field names.
     *
     * @param array         $values         Array of values to update.
     * @return bool                         True on success or false on failure.
     */
    function update(array $values)
    {
        if (empty($values))
        {
            return true;
        }

        $this->args = array_merge(array_values($values), $this->args);

        return $this->query(
            'UPDATE ' . $this->table . "\nSET\n    "
                . implode("=?,\n    ", array_keys($values)) . '=?'
                . (!empty($this->where) ? "\nWHERE " . $this->where : ''),
            $this->args
        );
    }



    /**
     * Exequtes DELETE query using statement's table name and conditions.
     *
     * @return bool                         True on success or false on failure.
     */
    function delete()
    {
        return $this->query(
            'DELETE FROM ' . $this->table
                . (!empty($this->where) ? "\nWHERE " . $this->where : ''),
            $this->args
        );
    }



    private function query($statement, $args = null,
        $canCacheRow = false, $needFetch = false, $fetch_style = PDO::FETCH_ASSOC)
    {
        $args = (array)$args;

        if ($needFetch)
        {
            $result = $this->cacheGetStatement($statement, $args, $fetch_style);
        }

        if (isset($result))
        {
            ++$this->cachedCount;
        }
        else
        {
            $this->logAdd($statement, $args);
            $sth = $this->apdo->pdo()->prepare($statement);
            $result = $sth->execute((array)$args);
            ++$this->executedCount;
            $this->rowCount = $sth->rowCount();

            if ($needFetch)
            {
                $result = $sth->fetchAll($fetch_style);
                $this->cacheSetStatement($statement, $args, $fetch_style, $result, $canCacheRow);
            }

            $sth->closeCursor();
        }

        if ($needFetch)
        {
            $this->rowCount = empty($result) ? 0 : count($result);
        }
        else
        {
            $this->cacheClear();
        }

        $this->lastQuery = $statement;
        $this->last = $this;

        return $result;
    }



    private function cacheKeyStatement($statement, $args, $fetch_style)
    {
        return 'st/' . md5($statement . '-args-' . implode('-', $args) . '-fs-' . $fetch_style);
    }



    private function cacheKeyRow($id, $fetch_style)
    {
        return 'id/' . md5($this->table . '-id-' . $id . '-fields-' . $this->fields . '-fs-' . $fetch_style);
    }



    private function cacheSetStatement($statement, $args, $fetch_style, $result, $canCacheRow)
    {
        if (isset($this->cache))
        {
            $this->cache->set($this->cacheKeyStatement($statement, $args, $fetch_style), $result);

            if (
                $canCacheRow
                && !empty($result)
                && empty($this->groupby)
                && !is_array($this->pkey) # references can't use complex keys
            ) {
                $this->cacheSetRow($result, $fetch_style);
            }
        }
    }



    private function cacheSetRow($result, $fetch_style)
    {
        if (isset($result[$this->pkey]))
        {
            $this->cache->set($this->cacheKeyRow($result[$this->pkey], $fetch_style), $result);
        }
        else
        {
            foreach ($result as $row)
            {
                if (isset($row[$this->pkey]))
                {
                    $this->cache->set($this->cacheKeyRow($row[$this->pkey], $fetch_style), $row);
                }
            }
        }
    }



    private function cacheGetStatement($statement, $args, $fetch_style)
    {
        return isset($this->cache)
            ? $this->cache->get($this->cacheKeyStatement($statement, $args, $fetch_style))
            : null;
    }



    private function cacheGetRow($id)
    {
        return isset($this->cache)
            ? $this->cache->get($this->cacheKeyRow($id))
            : null;
    }



    /**
     * Clears cache of the statement.
     */
    function cacheClear()
    {
        if (isset($this->cache))
        {
            $this->cache->clear();
        }
    }



    private function referrers_checkCachedRows($key, &$item, &$index, &$cached, &$keys)
    {
        $k = $item[$key];
        if (isset($k))
        {
            $index[$k] [] = & $item;
            if (empty($cached[$k]) && empty($keys[$k]))
            {
                $cache = $this->cacheGetRow($k);
                if (isset($cache))
                {
                    $cached[$k] = $cache;
                }
                else
                {
                    $keys[$k] = $k;
                }
            }
        }
    }



    /**
     * Executes SELECT query with condition on primary key,
     * which values extracted from specified field of data.
     *
     * (For simple, this method selects "parents" of $data.)
     *
     * Result values referenced with data using $referrer and $reference names.
     *      Items in data will have value with key $reference,
     *          that references to corresponding item of result, if any.
     *      Items in result will have value with key $referrer,
     *          that contains array of references to corresponding items of data.
     *
     * Some or all items of result can be retrieved from cache.
     *
     * Example:
     *
     *  $fruits = [
     *      ['id' => 1, 'name' => 'apple1', 'tree' => 1],
     *      ['id' => 2, 'name' => 'apple2', 'tree' => 1],
     *      ['id' => 3, 'name' => 'orange', 'tree' => 2],
     *  ];
     *
     *  $trees = $apdo->from('tree')
     *      ->referrers($fruits, 'fruits', 'tree')
     *      ->all();
     *
     * Result of that will be:
     *
     *  $trees == [
     *      ['id' => 1, 'name' => 'apple tree', 'fruits' => [
     *              &['id' => 1, 'name' => 'apple1', 'tree' => &reqursion],
     *              &['id' => 2, 'name' => 'apple2', 'tree' => &reqursion],
     *          ]]
     *      ['id' => 2, 'name' => 'orange tree', 'fruits' => [
     *              &['id' => 3, 'name' => 'orange', 'tree' => &reqursion],
     *          ]],
     *  ];
     *
     *  $fruits == [
     *      ['id' => 1, 'name' => 'apple1',
     *              'tree' => &['id' => 1, 'name' => 'apple tree', 'fruits' => &recursion],
     *          ],
     *      ['id' => 2, 'name' => 'apple2',
     *              'tree' => &['id' => 1, 'name' => 'apple tree', 'fruits' => &recursion],
     *          ],
     *      ['id' => 3, 'name' => 'orange',
     *              'tree' => &['id' => 1, 'name' => 'orange tree', 'fruits' => &recursion],
     *          ],
     *  ];
     *
     * @param array         $data           Data.
     * @param string        $referrer       Name of references in result array
     * @param string        $reference      Name of references in data array.
     * @param string        $key            Key name, that used to extract values for condition.
     *                                      By default is equal to $reference.
     * @param string        $pkey           Sets primary key to the statement. Will be used in condition.
     * @return array                        Result.
     */
    function referrers(&$data, $referrer, $reference, $key = null, $pkey = null)
    {
        if (empty($data))
        {
            return $this->nothing();
        }
        if (empty($key))
        {
            $key = $reference;
        }
        if (empty($pkey))
        {
            $pkey = $this->pkey;
        }

        $index = [];
        $cached = [];
        $keys = [];
        if (is_int(key($data)))
        {
            foreach ($data as &$item)
            {
                $this->referrers_checkCachedRows($key, $item, $index, $cached, $keys);
            }
            unset($item);
        }
        else
        {
            $this->referrers_checkCachedRows($key, $data, $index, $cached, $keys);
        }
        if (empty($keys))
        {
            $this->nothing();
        }
        else
        {
            $this->key($keys);
        }

        return $this
            ->handler(function ($result) use ($index, $cached, $referrer, $reference, $pkey)
            {
                $r = [];

                if (isset($cached))
                {
                    foreach ($cached as &$row)
                    {
                        $r [] = & $row;
                        foreach ($index[$row[$pkey]] as &$item)
                        {
                            $item[$reference] = & $row;
                            $row[$referrer] [] = & $item;
                        }
                        unset($item);
                    }
                    unset($row);
                }
                if (isset($result))
                {
                    foreach ($result as &$row)
                    {
                        $r [] = & $row;
                        foreach ($index[$row[$pkey]] as &$item)
                        {
                            $item[$reference] = & $row;
                            $row[$referrer] [] = & $item;
                        }
                        unset($item);
                    }
                    unset($row);
                }

                return $r;
            });
    }



    /**
     * Executes SELECT query with condition on specified field name,
     * which values extracted from primary keys of data.
     *
     * (For simple, this method selects "children" of $data.)
     *
     * Result values referenced with data using $referrer and $reference names.
     *      Items in data will have value with key $referrer,
     *          that contains array of references to corresponding items of result, if any.
     *      Items in result will have value with key $reference,
     *          that references to corresponding item of data.
     *
     * Example:
     *
     *  $trees = [
     *      ['id' => 1, 'name' => 'apple tree'],
     *      ['id' => 2, 'name' => 'orange tree'],
     *  ];
     *
     *  $fruits = $apdo->from('fruit')
     *      ->references($tree, 'fruits', 'tree')
     *      ->all();
     *
     * Result of that will be:
     *
     *  $trees == [
     *      ['id' => 1, 'name' => 'apple tree', 'fruits' => [
     *              &['id' => 1, 'name' => 'apple1', 'tree' => &reqursion],
     *              &['id' => 2, 'name' => 'apple2', 'tree' => &reqursion],
     *          ]]
     *      ['id' => 2, 'name' => 'orange tree', 'fruits' => [
     *              &['id' => 3, 'name' => 'orange', 'tree' => &reqursion],
     *          ]],
     *  ];
     *
     *  $fruits == [
     *      ['id' => 1, 'name' => 'apple1',
     *              'tree' => &['id' => 1, 'name' => 'apple tree', 'fruits' => &recursion],
     *          ],
     *      ['id' => 2, 'name' => 'apple2',
     *              'tree' => &['id' => 1, 'name' => 'apple tree', 'fruits' => &recursion],
     *          ],
     *      ['id' => 3, 'name' => 'orange',
     *              'tree' => &['id' => 2, 'name' => 'orange tree', 'fruits' => &recursion],
     *          ],
     *  ];
     *
     * @param array         $data           Data.
     * @param string        $referrer       Name of references in data array
     * @param string        $reference      Name of references in result array.
     * @param string        $key            Key name, that used in condition.
     *                                      By default is equal to $reference.
     * @param string        $pkey           Primary key, that used to extract values for condition.
     * @return array                        Result.
     */
    function references(&$data, $referrer, $reference, $key = null, $pkey = null)
    {
        unset($data[$referrer]);

        if (empty($data))
        {
            return $this->nothing();
        }
        if (empty($key))
        {
            $key = $reference;
        }
        if (empty($pkey))
        {
            $pkey = $this->pkey;
        }

        $index = [];
        if (is_int(key($data)))
        {
            foreach ($data as &$item)
            {
                $index[$item[$pkey]] = & $item;
            }
            unset($item);
        }
        else
        {
            $index[$data[$pkey]] = & $data;
        }

        return $this
            ->key(array_keys($index), $key)
            ->handler(function ($result) use ($index, $referrer, $reference, $key)
            {
                if (empty($result))
                {
                    return [];
                }

                $r = [];
                foreach ($result as &$row)
                {
                    $r [] = & $row;
                    $item = & $index[$row[$key]];
                    $item[$referrer] [] = & $row;
                    $row[$reference] = & $item;
                }
                unset($item);
                unset($row);

                return $r;
            });
    }

}
