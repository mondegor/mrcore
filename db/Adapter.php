<?php declare(strict_types=1);
namespace mrcore\db;
use Closure;
use mrcore\base\InterfaceConnection;
use mrcore\debug\DbProfiler;

require_once 'mrcore/base/InterfaceConnection.php';

/**
 * Адаптер соединения с хранилищем данных (СУБД).
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/db
 */
abstract class Adapter implements InterfaceConnection
{
    /**
     * Флаг автоматического закрытия соединения с БД.
     * Если этот флаг отключается, то закрытие соединения с БД
     * должно контроллироваться вручную.
     *
     * @var    bool
     */
    public bool $autoClose = true;

    /**
     * Генерация ошибок указанного уровня. По умолчанию E_USER_ERROR.
     *
     * @var    int
     */
    protected int $_userError = E_USER_ERROR;

    /**
     * Объект, который собирает статистику выполнения запросов.
     *
     * @var    DbProfiler
     */
    protected ?DbProfiler $_profiler = null;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      array  $params [hostName => string,
     *                             userName => string,
     *                             password => string,
     *                             dbName => string,
     *                             characterSet => string,
     *                             collationConnection => string,
     *                             timeZone => string OPTIONAL,
     *                             userError => int,
     *                             tryConnectCount => int,
     *                             generateErrors => bool,
     *                             connectSleep => int,
     *                             useProfiler => bool OPTIONAL]
     */
    public function __construct(array $params)
    {
        if (isset($params['userError']))
        {
            assert(is_int($params['userError']));
            $this->_userError = $params['userError'];
        }

        if (!empty($params['useProfiler']))
        {
            $this->_profiler = $this->_createDbProfiler();
        }
    }

    /**
     * Возвращение объекта профилирующего запросы.
     *
     * @return     DbProfiler|null
     */
    public function &getProfiler(): ?DbProfiler
    {
        return $this->_profiler;
    }

    /**
     * Returns escaped field value for use with sql request.
     *
     * @param      mixed  $value
     * @param      bool  $like OPTIONAL
     * @return     string
     */
    abstract public function escape($value, bool $like = false): string;

    /**
     * Выполнение заданного SQL-запроса.
     * Имеется поддержка placeholder-ов.
     *
     * @param      string  $sql
     * @param      mixed  $bind OPTIONAL
     * @return     resource
     */
    abstract public function execQuery(string $sql, $bind = []);

    /**
     * Выполнение заданного SQL-запроса.
     * Имеется поддержка placeholder-ов.
     *
     * @param      string  $sql
     * @param      mixed  $bind OPTIONAL
     * @return     Query|null
     */
    public function &query($sql, $bind = []): ?Query
    {
        $result = null;

        if ($resource = $this->execQuery($sql, $bind))
        {
            $result = $this->_createQuery($resource);
        }

        return $result;
    }

    /**
     * Fetch the current row.
     *
     * @param      resource  $resource
     * @param      bool $assoc OPTIONAL
     * @return     array [string => mixed, ...] or (if $assoc = false) [int => mixed, ...]
     */
    abstract public function fetch($resource, bool $assoc = true): array;

    /**
     * Fetches all SQL result rows as a sequential array.
     * Supported placeholders.
     *
     * @param      string  $sql
     * @param      mixed  $bind OPTIONAL
     * @param      Closure  $cbHandler OPTIONAL - static function (&[fetch' => true, 'row' => [], 'id' => null])
     * @return     array
     */
    abstract public function fetchAll(string $sql, $bind = [], Closure $cbHandler = null): array;

    /**
     * Fetches the first column of all SQL result rows as an array.
     * Supported placeholders.
     *
     * При $mirror = true создаётся зеркальный массив first column - first column.
     * (для использования функции isset вместо медленного in_array)
     *
     * @param      string  $sql
     * @param      mixed  $bind OPTIONAL
     * @param      bool  $mirror OPTIONAL
     * @return     array
     */
    abstract public function fetchCol(string $sql, $bind = [], bool $mirror = false): array;

    /**
     * Fetches all SQL result rows as an array of key-value pairs.
     * Supported placeholders.
     *
     * The first column is the key, the second column is the value.
     *
     * @param      string  $sql
     * @param      mixed  $bind OPTIONAL
     * @return     array
     */
    abstract public function fetchPairs(string $sql, $bind = []): array;

    /**
     * Fetches the first row of the SQL result.
     * Supported placeholders.
     *
     * @param      string  $sql
     * @param      mixed  $bind OPTIONAL
     * @param      bool  $assoc OPTIONAL
     * @return     array
     */
    abstract public function fetchRow(string $sql, $bind = [], bool $assoc = true): array;

    /**
     * Fetch the one field of row.
     * Supported placeholders.
     *
     * @param      string  $sql
     * @param      mixed  $bind OPTIONAL
     * @return     string
     */
    abstract public function fetchOne(string $sql, $bind = []): string;

    /**
     * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query.
     *
     * @param      resource  $resource
     * @return     int  The number of rows
     */
    abstract public function getNumRows($resource): int;

    /**
     * Returns the last inserted id by the previous INSERT query.
     *
     * @return     int  The last inserted id
     */
    abstract public function getLastInsertedId(): int;

    /**
     * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query.
     *
     * @return     int  The number of rows
     */
    abstract public function getAffectedRows(): int;

    /**
     * Inserts a table row with specified data.
     *
     * @param      string  $table
     * @param      array  $set
     * @param      bool   $escape OPTIONAL
     * @return     int  The number of affected rows.
     */
    abstract public function insert(string $table, array $set, bool $escape = true): int;

    /**
     * Updates table rows with specified data based on a WHERE clause.
     *
     * @param      string  $tableName
     * @param      array  $set
     * @param      mixed  $where
     * @param      bool   $escape
     * @return     int  The number of affected rows.
     */
    abstract public function update(string $tableName, array $set, $where, bool $escape = true): int;

    /**
     * Deletes table rows based on a WHERE clause.
     *
     * @param      string  $table
     * @param      mixed  $where
     * @param      bool  $confirmRemove OPTIONAL
     * @return     int  The number of affected rows.
     */
    abstract public function delete(string $table, $where, bool $confirmRemove = false): int;

    /**
     * Освобождение памяти от $resource.
     *
     * @param      resource  $resource
     */
    abstract public function freeResult($resource);

    /**
     * Получение структуры таблицы.
     *
     * @param      string  $tableName
     * @return     array [key => string|false, fields => [string => [type => string,
     *                                                    length => string,
     *                                                    isNull => bool,
     *                                                    values => [],
     *                                                    default => string,
     *                                                    isPrimary => bool,
     *                                                    isAutoInc => bool]]
     */
    abstract public function getTableStructure(string $tableName): array;

    /**
     * Подстановка placeholders в sql выражение.
     * :WARNING: Если в $expr выражении уже содержатся экранированные
     * строки и в этих строках содержится хотя бы один символ ?,
     * то данный метод эту ситуацию обработать не сможет.
     *
     * @param      string  $expr
     * @param      string|int|float|bool|array  $bind
     * @return     string
     */
    public function bind(string $expr, $bind): string
    {
        assert(is_scalar($bind) || is_array($bind));

        if (!is_array($bind))
        {
            $bind = [$bind];
        }

        ##################################################################################

        if (!empty($bind))
        {
            $expr = str_replace(['%', '?'], ['%%', '%s'], $expr);

            foreach ($bind as &$arg)
            {
                $arg = $this->_escapeValue($arg);
            }

            // unset($arg);

            $expr = vsprintf($expr, $bind);
        }

        return $expr;
    }

    /**
     * Деструктор класса.
     * Закрытие текущего соединения с БД.
     */
    public function __destruct()
    {// var_dump('call __destruct() of class: ' . get_class($this));
        if ($this->autoClose)
        {
            $this->close();
        }

        // /*__not_required__*/ parent::__destruct(); /*__WARNING_not_to_remove__*/
    }

    /**
     * Экранирование указанного значения в зависимости от типа.
     *
     * @param      string|int|float|bool|array|HelperExpr  $value
     * @return     string
     */
    protected function _escapeValue($value): string
    {
        if (is_int($value) || is_float($value))
        {
            return (string)$value;
        }

        if (is_bool($value))
        {
            return (string)((int)$value);
        }

        if (empty($value)/* || null === $value*/)
        {
            return "''";
        }

        if (is_string($value))
        {
            return "'" . $this->escape($value) . "'";
        }

        // если это выражение, то оно возвращается без экранирования
        if ($value instanceof HelperExpr)
        {
            return $value->get($this);
        }

        if (is_array($value))
        {
            $tmp = '';

            foreach ($value as $v)
            {
                assert(is_scalar($v));
                $tmp .= ', ' . $this->_escapeValue($v);
            }

            // аргумент превращается из массива
            // в строку элементов разделённых запятой
            return substr($tmp, 2);
        }

        return (string)$value;
    }

    /**
    * Соединение значений массива в строку для
    * применения её в sql обновлениях записи.
    *
    * @param      array  $set
    * @param      bool   $escape OPTIONAL
    * @return     string
    */
    protected function _setExpr(array $set, bool $escape = true): string
    {
        $result = '';

        if ($escape)
        {
            foreach ($set as $key => $value)
            {
                $result .= ', ' . $key . ' = ' . $this->_escapeValue($value);
            }
        }
        else
        {
            foreach ($set as $key => $value)
            {
                $result .= ', ' . $key . ' = ' . $value;
            }
        }

        return substr($result, 2);
    }

    /**
     * Convert an array, string
     * into a string to put in a WHERE clause.
     *
     * @param      array  $where
     * @return     string
     */
    protected function _whereExpr(array $where): string
    {
        $result = '';

        foreach ($where as $cond)
        {
            if ('' !== $cond)
            {
                $result .= ' AND (' . $cond . ')';
            }
        }

        if ('' !== $result)
        {
            $result = substr($result, 5);
        }

        return $result;
    }

    /**
     * Генерация ошибки БД.
     *
     * @param      string  $sql
     */
    abstract protected function _sqlTriggerError(string $sql): void;

    /**
     * @see    DbProfiler
     */
    protected function _createDbProfiler(): DbProfiler
    {
        require_once 'mrcore/debug/DbProfiler.php';

        return new DbProfiler();
    }

    /**
     * @see    Query
     */
    protected function _createQuery($resource): Query
    {
        require_once 'mrcore/db/Query.php';

        return new Query($this, $resource);
    }

}