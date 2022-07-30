<?php declare(strict_types=1);
namespace mrcore\storage\db;
use mrcore\debug\Assert;
use mrcore\debug\DatabaseProfiler;
use mrcore\debug\ProfileableInterface;
use mrcore\storage\ConnectionInterface;
use mrcore\storage\TransactionInterface;

/**
 * Абстракция адаптера соединения с базой данных.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractDatabase implements ConnectionInterface, TransactionInterface, ProfileableInterface
{
    /**
     * @see AbstractQuery::class
     */
    protected string $queryClass;

    /**
     * @see AbstractFeatures::class
     */
    protected string $featuresClass;

    /**
     * @see AbstractFeatures
     */
    protected AbstractFeatures $features;

    #################################### Methods #####################################

    /**
     * @param  array  $params [hostName => string,
     *                         userName => string,
     *                         password => string,
     *                         dbName => string,
     *                         characterSet => string,
     *                         collationConnection => string,
     *                         ?timeZone => string,
     *                         ?tryConnectCount => int,
     *                         connectSleep => int]
     */
    public function __construct(protected array $params, protected DatabaseProfiler|null $profiler = null) { }

    /**
     * @inheritdoc
     */
    public function getProfiler(): DatabaseProfiler|null
    {
        return $this->profiler;
    }

    /**
     * Возвращается объект расширяющий возможности данного класса.
     */
    public function getFeatures(): AbstractFeatures
    {
        if (!isset($this->features))
        {
            assert(Assert::instanceOf($this->featuresClass, AbstractFeatures::class), Assert::instanceOfMessage($this->featuresClass, AbstractFeatures::class));

            $this->features = new $this->featuresClass($this);
        }

        return $this->features;
    }

    /**
     * Returns escaped field value for use with sql request.
     *
     * @param  string|string[] $value
     */
    abstract public function escape(string|array $value, bool $like = false): string;

    /**
     * Выполнение заданного SQL-запроса.
     *
     * @param      array|null $bind {@see AbstractDatabase::bind()}
     */
    abstract public function execQuery(string $sql, array $bind = null): AbstractQuery|bool;

    /**
     * Fetches all SQL result rows as a sequential array.
     *
     * @param      array|null $bind {@see AbstractDatabase::bind()}
     * @return     array [[string => string|null, ...], ...] or [string => [string => string|null, ...], ...]
     */
    abstract public function fetchAll(string $sql, array $bind = null, bool $firstFieldAsKey = true): array;

    /**
     * Fetches the first column of all SQL result rows as an array.
     *
     * При $mirror = true создаётся зеркальный массив first column -> first column.
     * (например, для использования функции isset вместо медленного in_array)
     *
     * @param      array|null $bind {@see AbstractDatabase::bind()}
     * @return     string[] or array map[string]string
     */
    abstract public function fetchCol(string $sql, array $bind = null, bool $mirror = false): array;

    /**
     * Fetches all SQL result rows as an array of key-value pairs.
     * The first column is the key, the second column is the value.
     *
     * @param      array|null $bind {@see AbstractDatabase::bind()}
     * @return     array [string => string|null, ...]
     */
    abstract public function fetchPairs(string $sql, array $bind = null): array;

    /**
     * Fetches the first row of the SQL result.
     *
     * @param      array|null $bind {@see AbstractDatabase::bind()}
     * @return     array|null [string => string|null, ...]
     */
    abstract public function fetchRow(string $sql, array $bind = null, bool $assoc = true): ?array;

    /**
     * Fetch the one field of row.
     *
     * @return  string|false|null // false - если записи не найдено, null - если значение поля в БД равно NULL
     */
    abstract public function fetchOne(string $sql, array $bind = null): string|null|false;

    /**
     * Returns the last inserted id by the previous INSERT query.
     */
    abstract public function getLastInsertedId(): int;

    /**
     * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query.
     */
    abstract public function getAffectedRows(): int;

    /**
     * Подстановка placeholders в sql выражение.
     * :WARNING: Если в $expr выражении уже содержатся экранированные
     * строки и в этих строках содержится хотя бы один символ ?,
     * то данный метод эту ситуацию обработать не сможет.
     *
     * @param array $bind [string|int|float|bool|array|null, ...]
     */
    public function bind(string $expr, array $bind): string
    {
        if (!empty($bind))
        {
            $expr = str_replace(['%', '?'], ['%%', '%s'], $expr);

            foreach ($bind as &$arg)
            {
                $arg = $this->escapeValue($arg);
            }

            // unset($arg);

            $expr = vsprintf($expr, $bind);
        }

        return $expr;
    }

    /**
     * Экранирование указанного значения в зависимости от его типа.
     */
    public function escapeValue(string|int|float|bool|array|null $value): string
    {
        if (null === $value)
        {
            return 'NULL';
        }

        if (is_int($value) || is_float($value))
        {
            return (string)$value;
        }

        if (is_bool($value))
        {
            return (string)((int)$value);
        }

        if (empty($value)) // string or array
        {
            return "''";
        }

        if (is_string($value))
        {
            return "'" . $this->escape($value) . "'";
        }

        ##################################################################################

        $tmp = '';

        // $value превращается из массива в строку элементов разделённых запятой
        foreach ($value as $v)
        {
            assert(/*null !== $v && "''" !== $v && */!is_array($v));
            $tmp .= ', ' . $this->escapeValue($v);
        }

        return substr($tmp, 2);
    }

    /**
     * Закрытие текущего соединения с БД.
     */
    public function __destruct()
    {
        $this->close();
    }

    ##################################################################################

    protected function _createQuery(object $resource): AbstractQuery
    {
        assert(Assert::instanceOf($this->queryClass, AbstractQuery::class), Assert::instanceOfMessage($this->queryClass, AbstractQuery::class));

        return new $this->queryClass($this, $resource);
    }

}