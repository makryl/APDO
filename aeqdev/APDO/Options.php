<?php

namespace aeqdev\APDO;

use PDO;
use aeqdev\APDO;

/**
 * Stores options for statements.
 */
class Options
{

    /**
     * @var APDO
     */
    public $apdo;

    public $pkey = 'id';

    public $fetchMode = PDO::FETCH_ASSOC;
    public $fetchArg;
    public $fetchCtorArgs;

    /**
     * @var ILog
     */
    public $log;

    /**
     * @var ICache
     */
    public $cache;

    public $statementCount;
    public $executedCount;
    public $cachedCount;

    /**
     * @var Statement
     */
    public $last;

}
