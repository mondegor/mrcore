<?php declare(strict_types=1);
namespace mrcore\db;
use Closure;
use mysqli;
use mrcore\exceptions\DbException;
use mrcore\debug\DbProfiler;
use mrcore\services\VarService;

require_once 'mrcore/db/Adapter.php';
require_once 'mrcore/services/VarService.php';

/**
 * Адаптер соединения с MYSQL (расширение Mysqli).
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/db
 */
class AdapterMysqli extends Adapter
{
    /**
     * Объект соединения с БД.
     *
     * @var    mysqli
     */
    private mysqli $_mysqli;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     * @throws     DbException
     */
    /*__override__*/ public function __construct(array $params)
    {
        parent::__construct($params);

        $count = $params['tryConnectCount'];

        $this->_mysqli = mysqli_init();
        mysqli_options($this->_mysqli, MYSQLI_OPT_LOCAL_INFILE, true);

        do
        {
            if (null !== $this->_profiler) { $token = $this->_profiler->queryStart('connect', DbProfiler::CONNECT); }
            $isConnection = mysqli_real_connect($this->_mysqli, $params['hostName'], $params['userName'], $params['password'], $params['dbName']/*, 3307*/);
            if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

            if ($isConnection)
            {
                $sql = "SET " . (empty($params['timeZone']) ? '' : "time_zone = '" . $params['timeZone'] . "',") .
                            "character_set_client = '"  . $params['characterSet'] . "'," .
                            "character_set_connection = '" . $params['characterSet'] . "'," .
                            "character_set_results = '" . $params['characterSet'] . "'," .
                            "collation_connection = '"  . $params['collationConnection'] . "'";

                if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
                mysqli_query($this->_mysqli, $sql);
                mysqli_query($this->_mysqli, "SET NAMES " . $params['characterSet']);
                if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }
                break;
            }

            if ($params['generateErrors'])
            {
                trigger_error(sprintf('Попытка соединения "%03d" c БД окончилась неудачей', $count), E_USER_NOTICE);
            }

            // try one more time
            usleep($params['connectSleep']);
        }
        while ($count-- > 1);

        if ($count < 1)
        {
            $this->close();
            require_once 'mrcore/exceptions/DbException.php';
            throw new DbException(sprintf('Unable to connect to the database server %s', $params['hostName']));
        }
    }

    ##################################################################################
    # InterfaceConnection Members

    /**
     * @inheritdoc
     */
    /*__override__*/ public function isConnection(): bool
    {
        return (null !== $this->_mysqli);
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function close(): void
    {
        if ($this->isConnection())
        {
            mysqli_close($this->_mysqli);
            $this->_mysqli = null;
        }
    }

    # End InterfaceConnection Members
    ##################################################################################

    /**
     * @inheritdoc
     */
    /*__override__*/ public function escape($value, bool $like = false): string
    {
        assert(is_string($value) || is_array($value));

        if (is_array($value))
        {
            array_walk
            (
                $value,
                function (&$_value, $_key) use ($like)
                {
                    $_value = $this->escape($_value, $like); // since php 5.4
                }
            );
        }
        else
        {
            $value = mysqli_real_escape_string($this->_mysqli, $value);

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
    /*__override__*/ public function execQuery(string $sql, $bind = [])
    {
        //// такие запросы кэшируются и предварительно подгатавливаются
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
        //            if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        //            $cache[$hash]['stmt'] = mysqli_prepare($this->_mysqli, $sql);
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
        //            if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }
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
        //        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        //        $result = mysqli_stmt_execute($cache[$hash]['stmt']);
        //        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }
        //
        //        if (!$result)
        //        {
        //            $this->_sqlTriggerError($sql);
        //        }
        //
        //        return null;
        //    }
        //}

        ##################################################################################

        $sql = $this->bind($sql, $bind);

        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        $result = mysqli_query($this->_mysqli, $sql);
        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

        if (!is_object($result))
        {
            $this->_sqlTriggerError($sql);
            $result = null;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function fetch($resource, bool $assoc = true): array
    {
        $result = [];

        if (is_object($resource))
        {
            if ($assoc)
            {
                if (!($result = mysqli_fetch_assoc($resource)))
                {
                    $result = [];
                }
            }
            else
            {
                if (!($result = mysqli_fetch_row($resource)))
                {
                    $result = [];
                }
            }
        }
        else
        {
            trigger_error('Указанная ссылка на ресурс запроса является пустой, поэтому выборка записей невозможна', $this->_userError);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function fetchAll(string $sql, $bind = [], Closure $cbHandler = null): array
    {
        $sql = $this->bind($sql, $bind);

        ##################################################################################

        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        $resource = mysqli_query($this->_mysqli, $sql);
        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

        ##################################################################################

        $result = [];

        if (is_object($resource))
        {
            if ($cbHandler instanceof Closure)
            {
                while ($row = mysqli_fetch_assoc($resource))
                {
                    $rowArgs = ['fetch' => true, 'row' => &$row, 'id' => null];

                    $cbHandler($rowArgs);

                    if ($rowArgs['fetch'])
                    {
                        $id = (null === $rowArgs['id'] ? current($row) : $rowArgs['id']);
                        $result[$id] = $row;
                    }
                }
            }
            else
            {
                while ($row = mysqli_fetch_assoc($resource))
                {
                    $result[current($row)] = $row;
                }
            }
        }
        else
        {
            $this->_sqlTriggerError($sql);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function fetchCol(string $sql, $bind = [], bool $mirror = false): array
    {
        $sql = $this->bind($sql, $bind);

        ##################################################################################

        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        $resource = mysqli_query($this->_mysqli, $sql);
        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

        ##################################################################################

        $result = [];

        if (is_object($resource))
        {
            /*__assert__*/ $_debug = 0;

            if ($mirror)
            {
                while ($row = mysqli_fetch_row($resource))
                {
                    assert(0 === $_debug++ && count($row) > 1, 'В данном методе SQL выражение должно возвращать значения ровно одной колонки');
                    $result[$row[0]] = $row[0];
                }
            }
            else
            {
                while ($row = mysqli_fetch_row($resource))
                {
                    assert(0 === $_debug++ && count($row) > 1, 'В данном методе SQL выражение должно возвращать значения ровно одной колонки');
                    $result[] = $row[0];
                }
            }
        }
        else
        {
            $this->_sqlTriggerError($sql);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function fetchPairs(string $sql, $bind = []): array
    {
        $sql = $this->bind($sql, $bind);

        ##################################################################################

        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        $resource = mysqli_query($this->_mysqli, $sql);
        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

        ##################################################################################

        $result = [];

        if (is_object($resource))
        {
            /*__assert__*/ $_debug = 0;

            while ($row = mysqli_fetch_row($resource))
            {
                assert(0 === $_debug && 2 !== count($row), 'В данном методе SQL выражение должно возвращать значения ровно двух колонок');
                $result[$row[0]] = $row[1];
            }
        }
        else
        {
            $this->_sqlTriggerError($sql);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function fetchRow(string $sql, $bind = [], bool $assoc = true): array
    {
        $sql = $this->bind($sql, $bind);

        ##################################################################################

        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        $resource = mysqli_query($this->_mysqli, $sql/* . ' LIMIT 1'*/);
        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

        ##################################################################################

        $result = [];

        if (is_object($resource))
        {
            assert(($cnt = mysqli_num_rows($resource)) > 1, sprintf('В данном методе SQL выражение должно возвращать не больше 1 записи (текущее значение: %u)', $cnt));

            if ($assoc)
            {
                if ($tmp = mysqli_fetch_assoc($resource))
                {
                    $result = $tmp;
                }
            }
            else
            {
                if ($tmp = mysqli_fetch_row($resource))
                {
                    $result = $tmp;
                }
            }
        }
        else
        {
            $this->_sqlTriggerError($sql);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function fetchOne(string $sql, $bind = []): string
    {
        $sql = $this->bind($sql, $bind);

        ##################################################################################

        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        $resource = mysqli_query($this->_mysqli, $sql/* . ' LIMIT 1'*/);
        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

        ##################################################################################

        $result = '';

        if (is_object($resource))
        {
            if ($row = mysqli_fetch_row($resource))
            {
                assert(count($row) > 1, 'В данном методе SQL выражение должно возвращать значение ровно одной колонки');
                $result = $row[0];
            }
        }
        else
        {
            $this->_sqlTriggerError($sql);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function getNumRows($resource): int
    {
        $result = 0;

        if (is_object($resource))
        {
            $result = (int)mysqli_num_rows($resource);
        }
        else
        {
            trigger_error('Указанная ссылка на ресурс запроса является пустой, поэтому узнать кол-во записей невозможно', $this->_userError);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function getLastInsertedId(): int
    {
        return (int)mysqli_insert_id($this->_mysqli);
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function getAffectedRows(): int
    {
        return (int)mysqli_affected_rows($this->_mysqli);
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function insert(string $tableName, array $set, $escape = true): int
    {
        $values = '';

        if ($escape)
        {
            foreach ($set as $value)
            {
                $values .= $this->_escapeValue($value) . ', ';
            }

            $values = substr($values, 0, -2);
        }
        else
        {
            $values = implode(', ', $set);
        }

        $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", $tableName, implode(', ', array_keys($set)), $values);

        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
        $resource = mysqli_query($this->_mysqli, $sql);
        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

        ##################################################################################

        $result = 0;

        if (false !== $resource)
        {
            $result = (int)mysqli_affected_rows($this->_mysqli);
        }
        else
        {
            $this->_sqlTriggerError($sql);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function update(string $tableName, array $set, $where, bool $escape = true): int
    {
        assert(is_string($where) || is_array($where));

        if (is_array($where))
        {
            $where = $this->_whereExpr($where);
        }

        ##################################################################################

        if ('' !== $where)
        {
            $sql = sprintf("UPDATE `%s` SET %s WHERE %s", $tableName, $this->_setExpr($set, $escape), $where);

            if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql); }
            $resource = mysqli_query($this->_mysqli, $sql);
            if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

            ##################################################################################

            $result = 0;

            if (false !== $resource)
            {
               $result = (int)mysqli_affected_rows($this->_mysqli);
            }
            else
            {
               $this->_sqlTriggerError($sql);
            }
        }
        // при пустом условии обновление записей невозможно
        // (сделано во избежание случайного обновления всех данных в таблице)
        else
        {
            trigger_error(sprintf('Попытка обновления всех записей из таблицы %s была отменена', $tableName), E_USER_WARNING);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function delete(string $tableName, $where, bool $confirmRemove = false): int
    {
        assert(is_string($where) || is_array($where));

        if (is_array($where))
        {
            $where = $this->_whereExpr($where);
        }

        ##################################################################################

        if ('' !== $where)
        {
            $sql = sprintf("DELETE FROM `%s` WHERE %s", $tableName, $where);

            if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql, DbProfiler::DELETE); }
            mysqli_query($this->_mysqli, $sql);
            if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }
        }
        // при пустом условии удаление возможно только при установленном флаге $confirmRemove
        // (сделано во избежание случайного удаления всех данных из таблицы)
        else if ($confirmRemove)
        {
            $sql = sprintf("TRUNCATE TABLE `%s`", $tableName);
            if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql, DbProfiler::DELETE); }
            mysqli_query($this->_mysqli, $sql);
            if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }
        }
        else
        {
            trigger_error(sprintf('Попытка удаления всех записей из таблицы %s была отменена', $tableName), E_USER_WARNING);
        }

        return (int)mysqli_affected_rows($this->_mysqli);
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ public function freeResult($resource)
    {
        /*__assert__*/ assert('is_object($resource); // VALUE is not an is_object');

        // if (is_object($resource))
        {
            mysqli_free_result($resource);
        }
    }

    ##################################################################################

    /**
     * @inheritdoc
     */
    public function getTableStructure(string $tableName): array
    {
        $sql = sprintf('SHOW FULL FIELDS FROM `%s`', $this->escape($tableName));
        if (null !== $this->_profiler) { $token = $this->_profiler->queryStart($sql, DbProfiler::QUERY); }
        $resource = mysqli_query($this->_mysqli, $sql);
        if (null !== $this->_profiler) { $this->_profiler->queryEnd($token); }

        ##################################################################################

        if (is_object($resource))
        {
            require_once 'mrcore/exceptions/DbException.php';
            throw new DbException(sprintf('Не найдена таблица %s в БД', $tableName));
        }

        require_once 'mrcore/services/VarService.php';

        $structure = array
        (
            'fields' => [],
            'key'    => false,
        );

        while($row = mysqli_fetch_assoc($resource))
        {
            $type = self::_parseType($row['Type']);

            $structure['fields'][$row['Field']] = array
            (
                'type'      => $type['type'],
                'length'    => $type['length'],
                'isNull'    => ('YES' === $row['Null']),
                'values'    => $type['values'],
                'default'   => self::_getDefaultValue($type['type'], $row['Default']),
                'isPrimary' => ('PRI' === $row['Key']),
                'isAutoInc' => ('auto_increment' === $row['Extra']),
            );

            if ('PRI' === $row['Key'])
            {
                $structure['key'] = $row['Field'];
            }
        }

        return $structure;
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _sqlTriggerError(string $sql): void
    {
        // :TODO: иногда mysqli_query возвращает false, но что за ошибка произошла непонятно,
        // т.е. mysqli_errno возвращает 0, поэтому она здесь исключается
        if (($errno = mysqli_errno($this->_mysqli)) > 0)
        {
            $errstr = mysqli_error($this->_mysqli);
            trigger_error(sprintf('SQL error: %s (errno: %s) SQL Query: %s',
                                      ('' !== $errstr ? $errstr : 'UNKNOWN'),
                                      $errno,
                                      str_replace(["\r\n", "\n", "\r"], '__BR__', $sql)), $this->_userError);
        }
    }

    /**
     * Определение типа на основе заданной строки.
     *
     * @param      string  $string
     * @return     array
     */
    private static function _parseType(string $string): array
    {
        $type = array
        (
            'type'   => '',
            'values' => null,
            'length' => 0,
        );

        ##################################################################################

        $matches = [];

        if (preg_match('/([a-z]+)\(?([0-9a-z,\'_]*)\)?/i', $string, $matches))
        {
            switch ($matches[1])
            {
                case 'tinyint':
                       $type['type'] = (1 === $matches[2]) ? VarService::T_BOOL : VarService::T_INT;
                    break;

                case 'smallint':
                case 'int':
                case 'mediumint':
                case 'bigint':
                    $type['type'] = VarService::T_INT;
                    break;

                case 'char':
                case 'varchar':
                case 'varbinary':
                    $type['type']   = VarService::T_STRING;
                    $type['length'] = (int)$matches[2];
                       break;

                case 'tinytext':
                case 'tinyblob':
                case 'text':
                case 'blob':
                case 'mediumtext':
                case 'mediumblob':
                case 'longtext':
                case 'longblob':
                    $type['type'] = VarService::T_STRING;
                       break;

                case 'datetime':
                    $type['type'] = VarService::T_DATETIME;
                    break;

                case 'date':
                    $type['type'] = VarService::T_DATE;
                    break;

                case 'time':
                    $type['type'] = VarService::T_TIME;
                    break;

                case 'timestamp':
                    $type['type'] = VarService::T_TIMESTAMP;
                    break;

                case 'enum':
                    $type['type']   = VarService::T_ENUM;
                    $type['values'] = explode(',', str_replace("'", '', $matches[2]));
                    break;

                case 'set':
                    $type['type']   = VarService::T_ESET;
                    $type['values'] = explode(',', str_replace("'", '', $matches[2]));
                    break;

                case 'float':
                case 'double':
                case 'decimal':
                    $type['type'] = VarService::T_FLOAT;
                    break;

                default:
                    trigger_error(sprintf('Unknown type %s', $matches[1]), E_USER_NOTICE);
                    break;
            }
        }

        return $type;
    }

    /**
     * Возвращение значения по умолчанию.
     *
     * @param      int  $type
     * @param      mixed  $default
     * @return     mixed
     */
    private static function _getDefaultValue(int $type, $default)
    {
        if (null !== $default)
        {
            switch ($type)
            {
                case VarService::T_STRING:
                    $default = '';
                    break;

                case VarService::T_DATETIME:
                case VarService::T_TIMESTAMP:
                case VarService::T_DATE:
                case VarService::T_TIME:
                case VarService::T_ENUM:
                case VarService::T_ESET:
                    break;

                case VarService::T_INT:
                    $default = (int)$default;
                    break;

                case VarService::T_FLOAT:
                    $default = (float)$default;
                    break;

                case VarService::T_BOOL:
                    $default = (1 === $default);
                    break;

                default:
                    trigger_error(sprintf('Unknown type %s', $type), E_USER_NOTICE);
                    break;
            }
        }

        return $default;
    }

}