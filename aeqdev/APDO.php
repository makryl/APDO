<?php

namespace aeqdev;

use PDO;
use aeqdev\APDO\ILog;
use aeqdev\APDO\ICache;
use aeqdev\APDO\Options;
use aeqdev\APDO\Statement;

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

    protected $pdo;

    protected $dsn;
    protected $username;
    protected $password;
    protected $connectionOptions;

    /**
     * @var Options
     */
    protected $options;

    /**
     * Stores connection options.
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
    public function __construct($dsn, $username = null, $password = null, $options = null)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->connectionOptions = $options;

        $this->options = new Options();
        $this->options->apdo = $this;
    }

    /**
     * Returns PDO object.
     * At first call creates PDO (and establishes connection to database).
     *
     * @return PDO                          Associated PDO object.
     */
    public function pdo()
    {
        if (!isset($this->pdo)) {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->connectionOptions);
        }
        return $this->pdo;
    }

    /**
     * @return bool                         True if connection established, false otherwise.
     */
    public function connected()
    {
        return isset($this->pdo);
    }

    /**
     * @return int                          Count of created statements.
     */
    public function statementCount()
    {
        return $this->options->statementCount;
    }

    /**
     * @return int                          Count of queries sent to database.
     */
    public function executedCount()
    {
        return $this->options->executedCount;
    }

    /**
     * @return int                          Count of cached queries (that queries was not sent to database).
     */
    public function cachedCount()
    {
        return $this->options->cachedCount;
    }

    /**
     * @return Statement                    Last executed statement.
     */
    public function last()
    {
        return $this->options->last;
    }

    /**
     * Sets default primary key name for new statements.
     *
     * @param string $pkey                  Primary key name.
     */
    public function setPkey($pkey)
    {
        $this->options->pkey = $pkey;
    }

    /**
     * Sets default fetch style for new statements.
     * See details in PDOStatement::setFetchMode.
     *
     * @param string $fetchMode             PDO fetch mode.
     * @param string $fetchArg              Column number or class name or object.
     * @param string $fetchCtorArgs         Constructor arguments.
     */
    public function setFetchMode($fetchMode, $fetchArg = null, $fetchCtorArgs = null)
    {
        $this->options->fetchMode = $fetchMode;
        $this->options->fetchArg = $fetchArg;
        $this->options->fetchCtorArgs = $fetchCtorArgs;
    }

    /**
     * Sets or removes default logger of queries, sent to database, for new statements.
     * Logger must implements ILog interface with only one debug($msg) method.
     *
     * @param null|ILog             $log    Logger to set as default.
     */
    public function setLog($log = null)
    {
        $this->options->log = $log;
    }

    /**
     * Sets or removes default cacher for new statements.
     * Cacher must implements ICache interface with three simple methods:
     * get($name), set($name, $value) and clear().
     *
     * @param null|ICache           $cache Cacher to set as default.
     */
    public function setCache($cache = null)
    {
        $this->options->cache = $cache;
    }

    /**
     * Creates new statement.
     *
     * @param string        $statement      SQL statement.
     * @param string|array  $args           Argument or array of arguments for the statement.
     * @return Statement                    Created statement.
     */
    public function statement($statement = null, $args = null)
    {
        return new Statement($this->options, $statement, $args);
    }

    /**
     * Creates new statement and sets table name.
     *
     * @param string        $table          Table name.
     * @return Statement                    Created statement.
     */
    public function from($table)
    {
        return $this->statement()->table($table);
    }

    /**
     * Creates new statement and sets table name.
     * This method is alias for from().
     *
     * @param string        $table          Table name.
     * @return Statement                    Created statement.
     */
    public function in($table)
    {
        return $this->from($table);
    }

}
