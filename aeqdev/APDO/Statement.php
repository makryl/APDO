<?php

namespace aeqdev\APDO;

use PDO;

/**
 * Represents SQL statement.
 * Builds query and retrieve results.
 */
class Statement
{

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var ILog
     */
    protected $log;

    /**
     * @var ICache
     */
    protected $cache;

    protected $fetchMode;
    protected $fetchArg;
    protected $fetchCtorArgs;

    protected $nothing = false;
    protected $statement;
    protected $table;
    protected $pkey;
    protected $where;
    protected $groupby;
    protected $having;
    protected $orderby;
    protected $limit;
    protected $offset = 0;
    protected $columns = '*';
    protected $args = [];
    protected $handlers = [];

    protected $rowCount;
    protected $lastQuery;

    /**
     * Stores creator APDO options.
     * Sets SQL statement and it's arguments.
     *
     * @param Options       $options        Options object.
     * @param string        $statement      SQL statement.
     * @param string|array  $args           Argument or array of arguments for the statement.
     */
    public function __construct(Options $options, $statement = null, $args = null)
    {
        $this->options = $options;
        $this->statement = $statement;
        $this->args = isset($args) ? $args : [];

        ++$options->statementCount;

        $this
            ->pkey($options->pkey)
            ->fetchMode($options->fetchMode, $options->fetchArg, $options->fetchCtorArgs)
            ->log($options->log)
            ->cache($options->cache);
    }

    /**
     * @return Options                      Options object of current statement.
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * @return array                        Arguments of current statement.
     */
    public function args()
    {
        return $this->args;
    }

    /**
     * Adds statement and it's arguments to the log.
     *
     * @param string        $statement      SQL statement
     * @param string|array  $args           Argument or array of arguments for the statement.
     */
    protected function logAdd($statement, $args = null)
    {
        if (isset($this->log)) {
            $this->log->debug($statement);
            if (!empty($args)) {
                $this->log->debug(print_r($args, true));
            }
        }
    }

    /**
     * Sets or removes logger of queries, sent to database, for the statement.
     * Logger must implements ILog interface with only one debug($msg) method.
     *
     * @param null|ILog     $log            Logger.
     * @return static|$this                 Current statement.
     */
    public function log($log = null)
    {
        $this->log = $log;
        return $this;
    }

    /**
     * Sets or removes cacher for the statement.
     * Cacher must implements ICache interface with three simple methods:
     * get($name), set($name, $value) and clear().
     *
     * @param null|ICache   $cache          Cacher.
     * @return static|$this                 Current statement.
     */

    public function cache($cache = null)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return int                          Count of rows, affected by last query.
     */
    public function rowCount()
    {
        return $this->rowCount;
    }

    /**
     * @return string                       Last executed query.
     */
    public function lastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * After calling this method, result will always empty array,
     * and no query will sent to database.
     *
     * @return static|$this                 Current statement.
     */
    public function nothing()
    {
        $this->nothing = true;
        return $this;
    }

    /**
     * Sets table name for the statement.
     *
     * @param string        $table          Table name.
     * @return static|$this                 Current statement.
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Sets primary key name for the statement.
     *
     * @param string        $name           Primary key name, or array of names for complex primary key.
     * @return static|$this                 Current statement.
     */
    public function pkey($name)
    {
        $this->pkey = $name;
        return $this;
    }

    /**
     * Sets fetch style for the statement, that will be used in methods all(), page() and one().
     * See details in PDOStatement::setFetchMode
     *
     * @param string $fetchMode             PDO fetch mode.
     * @param string $fetchArg              Column number or class name or object.
     * @param string $fetchCtorArgs         Constructor arguments.
     * @return static|$this                 Current statement.
     */
    public function fetchMode($fetchMode, $fetchArg = null, $fetchCtorArgs = null)
    {
        $this->fetchMode = $fetchMode;
        $this->fetchArg = $fetchArg;
        $this->fetchCtorArgs = $fetchCtorArgs;
        return $this;
    }

    /**
     * Adds an JOIN to FROM section of the statement.
     *
     * @param string        $table          Table name to join with.
     * @param string        $on             Join condition.
     * @param string|array  $args           Argument or array of arguments for join conditions.
     * @param string        $joinType       Join type definition.
     * @return static|$this                 Current statement.
     */
    public function join($table, $on = null, $args = null, $joinType = '')
    {
        $this->table .= "\n$joinType JOIN " . $table;
        if (!empty($on)) {
            $this->table .= ' ON (' . $on . ')';
            $this->args = array_merge($this->args, (array)$args);
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
     * @return static|$this                 Current statement.
     */
    public function leftJoin($table, $on = null, $args = null)
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
     * @return static|$this                 Current statement.
     */
    public function where($where, $args = null, $or = false)
    {
        if (empty($this->where)) {
            $this->where = $where;
        } else {
            $this->where
                = '(' . $this->where . ')'
                . ($or ? ' OR ' : ' AND ')
                . '(' . $where . ')';
        }
        $this->args = array_merge($this->args, (array)$args);
        return $this;
    }

    /**
     * Adds conditions to the statement.
     *
     * Conditions appends to previously defined conditions with OR operator.
     *
     * @param string        $where          Conditions.
     * @param string|array  $args           Argument or array of arguments for conditions.
     * @return static|$this                 Current statement.
     */
    public function orWhere($where, $args = null)
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
     * @param string|array  $name           Column name or array of column names. By default primary key name is used.
     * @param bool          $or             Append type.
     *                                      False means append with AND operator,
     *                                      true means append with OR operator.
     * @return static|$this                 Current statement.
     */
    public function key($args, $name = null, $or = false)
    {
        if (empty($name)) {
            $name = $this->pkey;
        }
        if (is_array($name)) {
            foreach ($name as $i => $n) {
                $this->key($args[$i], $n, $or);
            }
            return $this;
        }
        if (is_array($args)) {
            $where = $name . ' IN (' . implode(',', array_fill(0, count($args), '?')) . ')';
        } else {
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
     * @param string|array  $name           Column name or array of column names. By default primary key name is used.
     * @return static|$this                 Current statement.
     */
    public function orKey($args, $name = null)
    {
        return $this->key($args, $name, true);
    }

    /**
     * Sets GROUP BY section of the statement.
     *
     * @param string        $groupby        Group by definition.
     * @return static|$this                 Current statement.
     */
    public function groupBy($groupby)
    {
        $this->groupby = $groupby;
        return $this;
    }

    /**
     * Sets HAVING section of the statement.
     *
     * @param string        $having         Having definition.
     * @param string|array  $args           Argument or array of arguments for having conditions.
     * @return static|$this                 Current statement.
     */
    public function having($having, $args = null)
    {
        $this->having = $having;
        $this->args = array_merge($this->args, (array)$args);
        return $this;
    }

    /**
     * Sets ORDER BY section of the statement.
     *
     * @param string        $orderby        Order by definition.
     * @return static|$this                 Current statement.
     */
    public function orderBy($orderby)
    {
        $this->orderby = $orderby;
        return $this;
    }

    /**
     * Adds column with sort direction to ORDER BY section of the statement.
     *
     * @param string        $column         Column name.
     * @param bool          $desc           Default false for ASC, true for DESC.
     * @return static|$this                 Current statement.
     */
    public function addOrderBy($column, $desc = false)
    {
        if (!empty($this->orderby)) {
            $this->orderby .= ', ';
        }
        $this->orderby .= $column;
        if ($desc) {
            $this->orderby .= ' DESC';
        }
        return $this;
    }

    /**
     * Sets LIMIT section of the statement.
     *
     * @param int           $limit          Limit definition.
     * @return static|$this                 Current statement.
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets OFFSET section of the statement.
     *
     * @param int           $offset         Offset definition.
     * @return static|$this                 Current statement.
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Sets columns of the statement.
     * First argument can be string with columns list, or an array of filed names.
     *
     * @param string|array  $columns        Columns definition or array of column names.
     * @return static|$this                 Current statement.
     */
    public function columns($columns)
    {
        $this->columns = implode(', ', (array)$columns);
        return $this;
    }

    /**
     * Adds handler function, that will be executed on result.
     * Handler function should have result argument and return modified result.
     *
     * @param callback      $handler        Handler function for results.
     * @return static|$this                 Current statement.
     */
    public function handler($handler)
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
    protected function handlers($result)
    {
        foreach ($this->handlers as $handler) {
            $result = $handler($result);
        }
        return $result;
    }

    /**
     * Builds SELECT query using statement's parameters.
     * @return string
     */
    public function buildSelect()
    {
        return 'SELECT ' . $this->columns
            . (!empty($this->table)     ? "\nFROM "     . $this->table      : '')
            . (!empty($this->where)     ? "\nWHERE "    . $this->where      : '')
            . (!empty($this->groupby)   ? "\nGROUP BY " . $this->groupby    : '')
            . (!empty($this->having)    ? "\nHAVING "   . $this->having     : '')
            . (!empty($this->orderby)   ? "\nORDER BY " . $this->orderby    : '')
            . (!empty($this->limit)     ? "\nLIMIT "    . $this->limit      : '')
            . (!empty($this->offset)    ? "\nOFFSET "   . $this->offset     : '');
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
     * @param string $fetchMode             PDO fetch mode.
     * @param string $fetchArg              Column number or class name or object.
     * @param string $fetchCtorArgs         Constructor arguments.
     * @return array|object[]               Result array.
     */
    public function fetchAll($fetchMode = null, $fetchArg = null, $fetchCtorArgs = null)
    {
        if (isset($fetchMode)) {
            $this->fetchMode($fetchMode, $fetchArg, $fetchCtorArgs);
        }
        return $this->handlers(
            $this->nothing
                ? []
                : $this->query(
                    $this->statement ? : $this->buildSelect(),
                    $this->args,
                    empty($this->statement),
                    true,
                    $this->fetchMode,
                    $this->fetchArg,
                    $this->fetchCtorArgs
                )
        );
    }

    /**
     * Same as method fetchAll() with fetch style PDO::FETCH_KEY_PAIR.
     * Intended to retrieve key-value result array.
     *
     * @param string        $name           Column name for result array values. Primary key by default.
     * @param string        $keyName        Column name for result array keys. Primary key by default.
     * @return array                        Result array.
     */
    public function fetchPairs($name = null, $keyName = null)
    {
        return $this
            ->columns([
                isset($keyName) ? $keyName : $this->pkey,
                isset($name) ? $name : $this->pkey,
            ])
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Same as method fetchAll() with offset calculated from page number and current limit.
     *
     * @param int           $page           Page number.
     * @return array|object[]               Result array.
     */
    public function fetchPage($page = 1)
    {
        return $this
            ->offset($this->limit * (($page ? : 1) - 1))
            ->fetchAll();
    }

    /**
     * Same as method fetchAll() with limit 1,
     * and returns first element of result array or null if result is empty.
     *
     * @param string $fetchMode             PDO fetch mode.
     * @param string $fetchArg              Column number or class name or object.
     * @param string $fetchCtorArgs         Constructor arguments.
     * @return array|object                 Result row.
     */
    public function fetchOne($fetchMode = null, $fetchArg = null, $fetchCtorArgs = null)
    {
        $r = $this
            ->limit(1)
            ->fetchAll($fetchMode, $fetchArg, $fetchCtorArgs);
        return isset($r[0]) ? $r[0] : null;
    }

    /**
     * Same as method fetchOne() with fetch stype PDO::FETCH_NUM.
     * Intended to use with php language construct list().
     *
     * @param string|array  $columns        Columns definition or array of column names.
     * @return array                        Result row.
     */
    public function fetchRow($columns)
    {
        return $this
            ->columns($columns)
            ->fetchOne(PDO::FETCH_NUM);
    }

    /**
     * Retrieves value of only one column in one row.
     * Same as method fetchOne() with fetch stype PDO::FETCH_COLUMN,
     * and returns value of first column or null if result is empty.
     *
     * @param string $column                Column name.
     * @return string                       Result cell value.
     */
    public function fetchCell($column)
    {
        return $this
            ->columns($column)
            ->fetchOne(PDO::FETCH_COLUMN);
    }

    /**
     * Executes SELECT COUNT(*) query, using statement's table name and conditions, and returns count.
     * The query can be cached.
     *
     * @return int                          Retrieved count.
     */
    public function count()
    {
        return $this->query(
            "SELECT COUNT(*)\nFROM " . $this->table
                . (!empty($this->where) ? "\nWHERE " . $this->where : ''),
            $this->args,
            false,
            true,
            PDO::FETCH_COLUMN)[0];
    }

    /**
     * Executes statement.
     *
     * @return bool                         True on success or false on failure.
     */
    public function execute()
    {
        return $this->query($this->statement, $this->args);
    }

    /**
     * Executes INSERT query using statement's table name,
     * and array of values with keys as column names.
     * Array of values also can be array of arrays for massive insertion.
     * Returns last inserted id.
     *
     * @param array|object  $values         Array of values or array of arrays of values to insert.
     * @return int                          Last inserted id.
     */
    public function insert($values)
    {
        if (is_object($values) || !isset($values[0])) {
            $values = [$values];
        }

        $names = [];
        foreach ($values[0] as $n => $a) {
            $names []= $n;
        }

        $this->args = [];
        foreach ($values as $v) {
            foreach ($v as $a) {
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

        return $this->options->apdo->pdo()->lastInsertId();
    }

    /**
     * Executes UPDATE query using statement's table name, conditions,
     * and array of values with keys as column names.
     *
     * @param array|object  $values         Array of values to update.
     * @return bool                         True on success or false on failure.
     */
    public function update($values)
    {
        if (empty($values)) {
            return true;
        }

        $set = '';
        $args = [];
        foreach ($values as $n => $a) {
            $set .= $n . "=?,\n    ";
            $args []= $a;
        }
        $set = substr($set, 0, -6);
        $this->args = array_merge($args, $this->args);

        return $this->query(
            'UPDATE ' . $this->table
            . "\nSET\n    ". $set
                . (!empty($this->where) ? "\nWHERE " . $this->where : ''),
            $this->args
        );
    }

    /**
     * Executes DELETE query using statement's table name and conditions.
     *
     * @return bool                         True on success or false on failure.
     */
    public function delete()
    {
        return $this->query(
            'DELETE FROM ' . $this->table
                . (!empty($this->where) ? "\nWHERE " . $this->where : ''),
            $this->args
        );
    }

    protected function query(
        $statement,
        $args = null,
        $canCacheRow = false,
        $needFetch = false,
        $fetchMode = null,
        $fetchArg = null,
        $fetchCtorArgs = null
    ) {
        $args = (array)$args;

        if ($needFetch) {
            $result = $this->cacheGetStatement($statement, $args, $fetchMode);
        }

        if (isset($result)) {
            ++$this->options->cachedCount;
        } else {
            $this->logAdd($statement, $args);
            $sth = $this->options->apdo->pdo()->prepare($statement);
            $result = $sth->execute($args);
            ++$this->options->executedCount;
            $this->rowCount = $sth->rowCount();

            if ($needFetch) {
                if (isset($fetchMode)) {
                    if (isset($fetchArg)) {
                        if (isset($fetchCtorArgs)) {
                            $result = $sth->fetchAll($fetchMode, $fetchArg, $fetchCtorArgs);
                        } else {
                            $result = $sth->fetchAll($fetchMode, $fetchArg);
                        }
                    } else {
                        $result = $sth->fetchAll($fetchMode);
                    }
                } else {
                    $result = $sth->fetchAll();
                }
                $this->cacheSetStatement($statement, $args, $fetchMode, $result, $canCacheRow);
            }

            $sth->closeCursor();
        }

        if ($needFetch) {
            $this->rowCount = empty($result) ? 0 : count($result);
        } else {
            $this->cacheClear();
        }

        $this->lastQuery = $statement;
        $this->options->last = $this;

        return $result;
    }

    protected function cacheKeyStatement($statement, $args, $fetchMode)
    {
        return 'st/' . md5($statement . '-args-' . implode('-', $args) . '-fetch-' . $fetchMode);
    }

    protected function cacheKeyRow($id, $fetchMode)
    {
        return 'id/' . md5($this->table . '-id-' . $id . '-columns-' . $this->columns . '-fetch-' . $fetchMode);
    }

    protected function cacheSetStatement($statement, $args, $fetchMode, $result, $canCacheRow)
    {
        if (isset($this->cache)) {
            $this->cache->set($this->cacheKeyStatement($statement, $args, $fetchMode), $result);

            if (
                $canCacheRow
                && !empty($result)
                && empty($this->groupby)
                && !is_array($this->pkey) # references can't use complex keys
            ) {
                $this->cacheSetRow($result, $fetchMode);
            }
        }
    }

    protected function cacheSetRow($result, $fetchMode)
    {
        if (is_object($result)) {
            if (isset($result->{$this->pkey})) {
                $this->cache->set($this->cacheKeyRow($result->{$this->pkey}, $fetchMode), $result);
            }
        } else if (isset($result[$this->pkey])) {
            $this->cache->set($this->cacheKeyRow($result[$this->pkey], $fetchMode), $result);
        } else {
            if (isset($result[0]) && is_object($result[0])) {
                foreach ($result as $row) {
                    if (isset($row->{$this->pkey})) {
                        $this->cache->set($this->cacheKeyRow($row->{$this->pkey}, $fetchMode), $row);
                    }
                }
            } else {
                foreach ($result as $row) {
                    if (isset($row[$this->pkey])) {
                        $this->cache->set($this->cacheKeyRow($row[$this->pkey], $fetchMode), $row);
                    }
                }
            }
        }
    }

    protected function cacheGetStatement($statement, $args, $fetchMode)
    {
        return isset($this->cache)
            ? $this->cache->get($this->cacheKeyStatement($statement, $args, $fetchMode))
            : null;
    }

    protected function cacheGetRow($id, $fetchMode)
    {
        return isset($this->cache)
            ? $this->cache->get($this->cacheKeyRow($id, $fetchMode))
            : null;
    }

    /**
     * Clears cache of the statement.
     */
    public function cacheClear()
    {
        if (isset($this->cache)) {
            $this->cache->clear();
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
     *              &['id' => 1, 'name' => 'apple1', 'tree' => &recursion],
     *              &['id' => 2, 'name' => 'apple2', 'tree' => &recursion],
     *          ]]
     *      ['id' => 2, 'name' => 'orange tree', 'fruits' => [
     *              &['id' => 3, 'name' => 'orange', 'tree' => &recursion],
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
     * @param array|object  $data           Data.
     * @param string        $referrer       Name of references in result array
     * @param string        $reference      Name of references in data array.
     * @param string        $key            Key name, that used to extract values for condition.
     *                                      By default is equal to $reference.
     * @param string        $pkey           Sets primary key to the statement. Will be used in condition.
     * @param bool          $unique         Set true for one-to-one references.
     * @return static|$this                 Current statement.
     */
    public function referrers(&$data, $referrer, $reference, $key = null, $pkey = null, $unique = false)
    {
        if (empty($data)) {
            return $this->nothing();
        }
        if (empty($key)) {
            $key = $reference;
        }
        if (empty($pkey)) {
            $pkey = $this->pkey;
        }

        $index = [];
        $cached = [];
        $keys = [];
        foreach ($data as $i => $v) {
            if (is_int($i)) {
                foreach ($data as $k => $item) {
                    $item = & $data[$k];
                    $this->referrers_checkCachedRows($key, $item, $index, $cached, $keys);
                    unset($item);
                }
            } else {
                $this->referrers_checkCachedRows($key, $data, $index, $cached, $keys);
            }
            break;
        }
        if (empty($keys)) {
            $this->nothing();
        } else {
            $this->key($keys);
        }

        return $this
            ->handler(function ($result) use ($index, $cached, $referrer, $reference, $pkey, $unique) {
                $r = [];
                if (!empty($cached)) {
                    if ($unique) {
                        $this->referrers_setValuesUnique($r, $index, $referrer, $reference, $pkey, $cached);
                    } else {
                        $this->referrers_setValues($r, $index, $referrer, $reference, $pkey, $cached);
                    }
                }
                if (!empty($result)) {
                    if ($unique) {
                        $this->referrers_setValuesUnique($r, $index, $referrer, $reference, $pkey, $result);
                    } else {
                        $this->referrers_setValues($r, $index, $referrer, $reference, $pkey, $result);
                    }
                }
                return $r;
            });
    }

    private function referrers_checkCachedRows($key, &$item, &$index, &$cached, &$keys)
    {
        $k = is_object($item) ? $item->{$key} : $item[$key];
        if (isset($k)) {
            $index[$k] [] = & $item;
            if (empty($cached[$k]) && empty($keys[$k])) {
                $cache = $this->cacheGetRow($k, $this->fetchMode);
                if (isset($cache)) {
                    $cached[$k] = $cache;
                } else {
                    $keys[$k] = $k;
                }
            }
        }
    }

    private function referrers_setValues(&$result, &$index, $referrer, $reference, $pkey, &$data)
    {
        foreach ($data as $i => $row) {
            $row = & $data[$i];
            $result [] = & $row;
            if (is_object($row)) {
                foreach ($index[$row->{$pkey}] as $k => $item) {
                    $item = $index[$row->{$pkey}][$k];
                    $item->{$reference} = $row;
                    $row->{$referrer} [] = $item;
                    unset($item);
                }
            } else {
                foreach ($index[$row[$pkey]] as $k => $item) {
                    $item = & $index[$row[$pkey]][$k];
                    $item[$reference] = & $row;
                    $row[$referrer] [] = & $item;
                    unset($item);
                }
            }
            unset($row);
        }
    }

    private function referrers_setValuesUnique(&$result, &$index, $referrer, $reference, $pkey, &$data)
    {
        foreach ($data as $i => $row) {
            $row = & $data[$i];
            $result [] = & $row;
            if (is_object($row)) {
                foreach ($index[$row->{$pkey}] as $k => $item) {
                    $item = $index[$row->{$pkey}][$k];
                    $item->{$reference} = $row;
                    $row->{$referrer} = $item;
                    unset($item);
                }
            } else {
                foreach ($index[$row[$pkey]] as $k => $item) {
                    $item = & $index[$row[$pkey]][$k];
                    $item[$reference] = & $row;
                    $row[$referrer] = & $item;
                    unset($item);
                }
            }
            unset($row);
        }
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
     *              &['id' => 1, 'name' => 'apple1', 'tree' => &recursion],
     *              &['id' => 2, 'name' => 'apple2', 'tree' => &recursion],
     *          ]]
     *      ['id' => 2, 'name' => 'orange tree', 'fruits' => [
     *              &['id' => 3, 'name' => 'orange', 'tree' => &recursion],
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
     * @param array|object  $data           Data.
     * @param string        $referrer       Name of references in data array
     * @param string        $reference      Name of references in result array.
     * @param string        $key            Key name, that used in condition.
     *                                      By default is equal to $reference.
     * @param string        $pkey           Primary key, that used to extract values for condition.
     * @param bool          $unique         Set true for one-to-one references.
     * @return static|$this                 Current statement.
     */
    public function references(&$data, $referrer, $reference, $key = null, $pkey = null, $unique = false)
    {
        if (empty($data)) {
            return $this->nothing();
        }
        if (empty($key)) {
            $key = $reference;
        }
        if (empty($pkey)) {
            $pkey = $this->pkey;
        }

        $index = [];
        foreach ($data as $i => $v) {
            if (is_int($i)) {
                foreach ($data as $k => $item) {
                    $item = & $data[$k];
                    if (is_object($item)) {
                        $item->{$referrer} = [];
                        $index[$item->{$pkey}] = $item;
                    } else {
                        $item[$referrer] = [];
                        $index[$item[$pkey]] = & $item;
                    }
                    unset($item);
                }
            } else {
                if (is_object($data)) {
                    $data->{$referrer} = [];
                    $index[$data->{$pkey}] = $data;
                } else {
                    $data[$referrer] = [];
                    $index[$data[$pkey]] = & $data;
                }
            }
            break;
        }

        return $this
            ->key(array_keys($index), $key)
            ->handler(function ($result) use ($index, $referrer, $reference, $key, $unique) {
                if (empty($result)) {
                    return [];
                }

                $r = [];

                if ($unique) {
                    foreach ($result as $k => $row) {
                        $row = & $result[$k];
                        $r [] = & $row;
                        if (is_object($row)) {
                            $item = $index[$row->{$key}];
                            $item->{$referrer} = $row;
                            $row->{$reference} = $item;
                        } else {
                            $item = & $index[$row[$key]];
                            $item[$referrer] = & $row;
                            $row[$reference] = & $item;
                        }
                        unset($row);
                        unset($item);
                    }
                } else {
                    foreach ($result as $k => $row) {
                        $row = & $result[$k];
                        $r [] = & $row;
                        if (is_object($row)) {
                            $item = $index[$row->{$key}];
                            $item->{$referrer} [] = $row;
                            $row->{$reference} = $item;
                        } else {
                            $item = & $index[$row[$key]];
                            $item[$referrer] [] = & $row;
                            $row[$reference] = & $item;
                        }
                        unset($row);
                        unset($item);
                    }
                }

                return $r;
            });
    }

    public function referrersUnique(&$data, $referrer, $reference, $key = null, $pkey = null)
    {
        return $this->referrers($data, $referrer, $reference, $key, $pkey, true);
    }

    public function referencesUnique(&$data, $referrer, $reference, $key = null, $pkey = null)
    {
        return $this->references($data, $referrer, $reference, $key, $pkey, true);
    }

}
