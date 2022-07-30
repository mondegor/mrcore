<?php declare(strict_types=1);
namespace mrcore\storage\db\mysql;
use mrcore\exceptions\UnableConnectionException;
use mrcore\storage\db\AbstractDatabase;
use mrcore\storage\exceptions\InvalidSqlQueryException;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;

/**
 * Адаптер соединения с MYSQL (расширение Mysqli).
 *
 * @author  Andrey J. Nazarov
 */
class MysqlDatabase extends AbstractDatabase
{
    /**
     * @inheritdoc
     */
    protected string $featuresClass = MysqlFeatures::class;

    /**
     * @inheritdoc
     */
    protected string $queryClass = MysqlQuery::class;

    /**
     * Объект соединения с БД.
     */
    private ?mysqli $mysqli = null;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function getProvider(): string
    {
        return $this->params['provider'];
    }

    /**
     * @inheritdoc
     * @throws  UnableConnectionException
     */
    public function open(): void
    {
        if (null !== $this->mysqli)
        {
            // WARNING
            return;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // :TODO: не относится к конкретному соединению

        ##################################################################################

        $params = $this->params;

        $this->mysqli = mysqli_init();
        // mysqli_options($this->mysqli, MYSQLI_OPT_LOCAL_INFILE, true);

        $count = $params['tryConnectCount'] ?? 1;

        do
        {
            try
            {
                if (null !== $this->profiler) { $token = $this->profiler->queryStart('connect', $this->profiler::CONNECT); }
                $isConnection = mysqli_real_connect($this->mysqli, $params['hostName'], $params['userName'], $params['password'], $params['dbName'], $params['dbPort'] ?? null);
                if (null !== $this->profiler) { $this->profiler->queryEnd($token); }

                if ($isConnection)
                {
                    $this->_query
                    (
                        "SET " . (empty($params['timeZone']) ? '' : "time_zone = '" . $params['timeZone'] . "',") .
                            "character_set_client = '"  . $params['characterSet'] . "'," .
                            "character_set_connection = '" . $params['characterSet'] . "'," .
                            "character_set_results = '" . $params['characterSet'] . "'," .
                            "collation_connection = '"  . $params['collationConnection'] . "'"
                    );

                    $this->_query("SET NAMES " . $params['characterSet']);

                    return; // :WARNING:
                }
            }
            catch (mysqli_sql_exception $e)
            {
                if (2002 !== $e->getCode()) // 2002 - Connection refused
                {
                    throw $e;
                }
            }

            if ($count-- < 2)
            {
                break;
            }

            // try one more time
            usleep($params['connectSleep']);
        }
        while (true);

        if ($count < 1)
        {
            $this->close();
            // :TODO: добавить сцеплённую ошибку для разработчиков
            throw new UnableConnectionException(sprintf('Unable to connect to the database server %s', $params['hostName']));
        }
    }

    /**
     * @inheritdoc
     */
    public function isConnection(): bool
    {
        return (null !== $this->mysqli);
    }

    /**
     * @inheritdoc
     */
    public function close(): void
    {
        if (null !== $this->mysqli)
        {
            mysqli_close($this->mysqli);
            $this->mysqli = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function escape(string|array $value, bool $like = false): string
    {
        if (is_array($value))
        {
            array_walk
            (
                $value,
                function (&$_value, $_key) use ($like)
                {
                    $_value = $this->escape($_value, $like);
                }
            );
        }
        else
        {
            $value = mysqli_real_escape_string($this->mysqli, $value);

            if ($like)
            {
                // символы % _ \ экранируются, т.к. они являются служебными в sql выражении
                $value = addcslashes($value, '%_');
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function execQuery(string $sql, array $bind = null): MysqlQuery|bool
    {
        //// такие запросы кэшируются и предварительно подготавливаются
        //if ('INSERT' == substr($sql, 0, 6))
        //{
        //    // :TODO: перенести в отдельную функцию
        //    // :TODO: $cache очищать по необходимости
        //    static $cache = [];
        //
        //    $hash = md5($sql);
        //    $mysqliQuery = false;
        //
        //    if (!isset($cache[$hash]))
        //    {
        //        var_dump('$cache[$hash] INIT()');
        //        $cache[$hash] = ['sql' => $sql];
        //
        //        // i - int
        //        // d - float and double
        //        // s - string
        //        $bindTypes = '';
        //        $bindCount = 0;
        //
        //        foreach ($bind as $param)
        //        {
        //            // если в параметрах задан массив, то такого вида запрос отменяется
        //            // т.к. не поддерживается mysqli_stmt_bind_param
        //            if (is_array($param))
        //            {
        //                $mysqliQuery = true;
        //                break;
        //            }
        //
        //            $bindTypes .= (is_int($param) || is_bool($param) ? 'i' : (is_float($param) ? 'd' : 's'));
        //            $bindCount++;
        //        }
        //
        //        ##################################################################################
        //
        //        if (!$mysqliQuery)
        //        {
        //            if (null !== $this->profiler) { $token = $this->profiler->queryStart($sql); }
        //            $cache[$hash]['stmt'] = mysqli_prepare($this->mysqli, $sql);
        //
        //            if ($bindCount > 0)
        //            {
        //                $_bind = [];
        //                $stmtBind = [&$cache[$hash]['stmt'], &$bindTypes];
        //
        //                for ($i = 0; $i < $bindCount; $i++)
        //                {
        //                    $_bind[$i] = $bind[$i];
        //                    $stmtBind[$i + 2] = &$_bind[$i];
        //                }
        //
        //                $cache[$hash]['bind'] = &$_bind;
        //                call_user_func_array('mysqli_stmt_bind_param', $stmtBind);
        //            }
        //            if (null !== $this->profiler) { $this->profiler->queryEnd($token); }
        //        }
        //    }
        //    else if ($cache[$hash]['sql'] != $sql)
        //    {
        //        $mysqliQuery = true;
        //        trigger_error('Хэши двух разных запросов совпали, поэтому будет работать стандартный mysqli_query: [%s] #### [%s]', $cache[$hash]['sql'], $sql);
        //    }
        //
        //    if (!$mysqliQuery)
        //    {
        //        // :TODO: при первой вставки бинденга не нужно, т.к. он ранее был
        //        print $sql . ' :: ' . implode('|', $bind) . "<br>\n";
        //        $i = 0;
        //        foreach ($bind as $param)
        //        {
        //            $cache[$hash]['bind'][$i++] = $param;
        //        }
        //
        //        if (null !== $this->profiler) { $token = $this->profiler->queryStart($sql); }
        //        $result = mysqli_stmt_execute($cache[$hash]['stmt']);
        //        if (null !== $this->profiler) { $this->profiler->queryEnd($token); }
        //
        //        if (!$result)
        //        {
        //            $this->_sqlThrowException($sql);
        //        }
        //
        //        return null;
        //    }
        //}

        ##################################################################################

        $result = $this->_query($sql, $bind);

        if (is_object($result))
        {
            return $this->_createQuery($result);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function fetchAll(string $sql, array $bind = null, bool $firstFieldAsKey = true): array
    {
        $resource = $this->_fetch($sql, $bind);

        if ($firstFieldAsKey)
        {
            $result = [];

            while (null !== ($row = mysqli_fetch_assoc($resource)))
            {
                assert(null !== current($row));
                $result[current($row)] = $row;
            }
        }
        else
        {
            $result = mysqli_fetch_all($resource, MYSQLI_ASSOC);
        }

        mysqli_free_result($resource);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function fetchCol(string $sql, array $bind = null, bool $mirror = false): array
    {
        $resource = $this->_fetch($sql, $bind);

        if ($mirror)
        {
            $result = [];

            while (null !== ($row = mysqli_fetch_row($resource)))
            {
                assert(1 === count($row), 'В данном методе SQL выражение должно возвращать значения ровно одной колонки');
                assert(null !== $row[0]);
                $result[$row[0]] = $row[0];
            }
        }
        else
        {
            $result = mysqli_fetch_all($resource, MYSQLI_NUM);
            assert(isset($result[0]) && 1 === count($result[0]), 'В данном методе SQL выражение должно возвращать значения ровно одной колонки');
            assert(isset($result[0]) && null !== $result[0][0]);
        }

        mysqli_free_result($resource);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function fetchPairs(string $sql, array $bind = null): array
    {
        $resource = $this->_fetch($sql, $bind);

        $result = [];

        while (null !== ($row = mysqli_fetch_row($resource)))
        {
            assert(2 === count($row), 'В данном методе SQL выражение должно возвращать значения ровно двух колонок');
            assert(null !== $row[0]);
            $result[$row[0]] = $row[1];
        }

        mysqli_free_result($resource);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function fetchRow(string $sql, array $bind = null, bool $assoc = true): ?array
    {
        $resource = $this->_fetch($sql, $bind);

        assert(($cnt = mysqli_num_rows($resource)) < 2, sprintf('В данном методе SQL выражение %s должно возвращать не больше 1 записи (текущее значение: %u)', $sql, $cnt));

        $row = mysqli_fetch_array($resource, $assoc ? MYSQLI_ASSOC : MYSQLI_NUM);

        mysqli_free_result($resource);

        return $row;
    }

    /**
     * @inheritdoc
     */
    public function fetchOne(string $sql, array $bind = null): string|null|false
    {
        $resource = $this->_fetch($sql, $bind);

        $row = mysqli_fetch_row($resource);

        mysqli_free_result($resource);

        if (null === $row) // если запрос ничего не вернул
        {
            return false;
        }

        assert(1 === count($row), 'В данном методе SQL выражение должно возвращать значение ровно одной колонки');
        assert(null === $row[0] || is_string($row[0]));

        return $row[0];
    }

    /**
     * @inheritdoc
     */
    public function getLastInsertedId(): int
    {
        return mysqli_insert_id($this->mysqli);
    }

    /**
     * @inheritdoc
     */
    public function getAffectedRows(): int
    {
        $count = mysqli_affected_rows($this->mysqli);

        return $count > 0 ? $count : 0;
    }

    /**
     * @inheritdoc
     */
    public function beginTransaction(): bool
    {
        // set session transaction isolation level read committed
        return mysqli_begin_transaction($this->mysqli);
    }

    /**
     * @inheritdoc
     */
    public function commit(): bool
    {
        return  mysqli_commit($this->mysqli);
    }

    /**
     * @inheritdoc
     */
    public function rollBack(): bool
    {
        return  mysqli_rollback($this->mysqli);
    }

    ##################################################################################

    /**
     * Обработка запроса выборки данных из БД.
     *
     * @param  array|null $bind {@see AbstractDatabase::bind()}
     */
    protected function _fetch(string $sql, array $bind = null): mysqli_result
    {
        $resource = $this->_query($sql, $bind);

        if (is_bool($resource)) // если ошибочно задан запрос типа INSERT, UPDATE, DELETE
        {
            throw new InvalidSqlQueryException($sql);
        }

        return $resource;
    }

    /**
     * SQL запрос к БД.
     *
     * @param  array|null $bind {@see AbstractDatabase::bind()}
     */
    protected function _query(string $sql, array $bind = null): mysqli_result|bool
    {
        if (null !== $bind)
        {
            $sql = $this->bind($sql, $bind);
        }

        try
        {
            if (null === $this->profiler)
            {
                return mysqli_query($this->mysqli, $sql);
            }

            $token = $this->profiler->queryStart($sql);
            $result = mysqli_query($this->mysqli, $sql);
            $this->profiler->queryEnd($token);
        }
        catch (mysqli_sql_exception $e)
        {
            throw new InvalidSqlQueryException($sql, 0, $e);
        }

        return $result;
    }

}