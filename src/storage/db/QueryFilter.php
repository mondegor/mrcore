<?php declare(strict_types=1);
namespace mrcore\storage\db;

// :TODO: перенести в mrcms

/**
 * Используется для формирования SQL условий выборки записей
 * в виде массива на основании описания структуры фильтра и внешних значений.
 * При этом добавляются только те условия, для которых присутствуют внешние значения.
 *
 * @author  Andrey J. Nazarov
 */
class QueryFilter
{
    /**
     * Типы элементов фильтра.
     */
    public const INT = 1, // целое значение
                 STRING = 2, // строковое значение
                 INT_ARRAY = 3, // массив целых значений
                 STRING_ARRAY = 4, // массив строковых значений
                 INT_INTERVAL = 5, // целочисленный интервал
                 FLOAT_INTERVAL = 6, // интервал вещественных чисел
                 DATE_INTERVAL = 7; // интервал времени

    /**
     * Привязка обработчиков к элементам фильтра.
     *
     * @var  array [int => string, ...]
     */
    private const BIND_HANDLERS = array
    (
        self::INT => '_conditionValue',
        self::STRING => '_conditionValue',
        self::INT_ARRAY => '_conditionValueArray',
        self::STRING_ARRAY => '_conditionValueArray',
        self::INT_INTERVAL => '_conditionNumberInterval',
        self::FLOAT_INTERVAL => '_conditionNumberInterval',
        self::DATE_INTERVAL => '_conditionDateInterval',
    );

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param  AbstractDatabase $db
     * @param  array   $filterItems // описание структуры фильтра
     *                              [[type => int, fieldName => string, params => [string => mixed, ...]], ...]
     */
    public function __construct(private AbstractDatabase $db, private array $filterItems) { }

    /**
     * Добавление в массив $conditions SQL условий выборки записей на основании
     * описания структуры фильтра и внешних значений.
     *
     * @param  string[] $conditions
     * @param  array $values [string => mixed, ...]
     * @return bool
     */
    public function setConditions(array &$conditions, array $values): bool
    {
        $result = false;

        foreach ($this->filterItems as $filterItem)
        {
            if (!isset(self::BIND_HANDLERS[$filterItem['type']]))
            {
                // error
                continue;
            }

            $handler = self::BIND_HANDLERS[$filterItem['type']];

            if ($this->$handler($conditions, $filterItem['fieldName'], $values, $filterItem['params']))
            {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Возвращается список SQL условий выборки записей на основании
     * описания структуры фильтра и внешних значений.
     *
     * @param  array $values [string => mixed, ...]
     * @return string[]
     */
    public function getConditions(array $values): array
    {
        $result = [];
        $this->setConditions($result, $values);

        return $result;
    }

    ##################################################################################

    /**
     * Возвращается SQL условие для выборки записей соответствующих указанному значению.
     *
     * @param  string[] $conditions
     * @param  string $fieldName
     * @param  array  $values [string => mixed, ...]
     * @param  array  $params [name => string]
     * @return bool
     */
    protected function _conditionValue(array &$conditions, string $fieldName, array $values, array $params): bool
    {
        $name = $params['name'];

        if (null !== $values[$name])
        {
            assert(is_string($values[$name]) || is_int($values[$name]));

            $conditions[] = $this->_createCondition(sprintf("%s = ?", $fieldName), [$values[$name]]);
            return true;
        }

        return false;
    }

    /**
     * Возвращается SQL условие для выборки записей соответствующих указанному списку значений.
     *
     * @param  string[]  $conditions
     * @param  string $fieldName
     * @param  array  $values [string => mixed, ...]
     * @param  array  $params [name => string]
     * @return bool
     */
    protected function _conditionValueArray(array &$conditions, string $fieldName, array $values, array $params): bool
    {
        $name = $params['name'];

        if (!empty($values[$name]))
        {
            assert(is_array($values[$name]) && array_is_list($values[$name]));

            $conditions[] = $this->_createCondition(sprintf("%s IN(?)", $fieldName), [$values[$name]]);
            return true;
        }

        return false;
    }

    /**
     * Возвращается SQL условие для выборки записей из указанного числового интервала.
     *
     * @param  string[]  $conditions
     * @param  string $fieldName
     * @param  array  $values [string => mixed, ...]
     * @param  array  $params [nameMin => string, nameMax => string]
     * @return bool
     */
    protected function _conditionNumberInterval(array &$conditions, string $fieldName, array $values, array $params): bool
    {
        $nameMin = $params['nameMin'];
        $nameMax = $params['nameMax'];

        if (null !== $values[$nameMin])
        {
            assert(is_float($values[$nameMin]) || is_int($values[$nameMin]));
            $tMin = is_float($values[$nameMin]) ? '%.4f' : '%d';

            if (null !== $values[$nameMax])
            {
                assert(is_float($values[$nameMax]) || is_int($values[$nameMax]));
                $tMax = is_float($values[$nameMax]) ? '%.4f' : '%d';

                if ($values[$nameMin] === $values[$nameMax])
                {
                    $conditions[] = sprintf('%s = ' . $tMax, $fieldName, $values[$nameMax]);
                    return true;
                }

                $conditions[] = sprintf('%1$s >= ' . $tMin . ' AND %1$s <= ' . $tMax, $fieldName, $values[$nameMin], $values[$nameMax]);
                return true;
            }

            $conditions[] = sprintf('%s >= ' . $tMin, $fieldName, $values[$nameMin]);
            return true;
        }

        if (null !== $values[$nameMax])
        {
            assert(is_float($values[$nameMax]) || is_int($values[$nameMax]));
            $tMax = is_float($values[$nameMax]) ? '%.4f' : '%d';

            $conditions[] = sprintf('%s <= ' . $tMax, $fieldName, $values[$nameMax]);
            return true;
        }

        return false;
    }

    /**
     * Возвращается SQL условие для выборки записей из указанного интервала времени.
     *
     * @param  string[]  $conditions
     * @param  string $fieldName
     * @param  array  $values [string => mixed, ...]
     * @param  array  $params [nameMin => string, nameMax => string]
     * @return bool
     */
    protected function _conditionDateInterval(array &$conditions, string $fieldName, array $values, array $params): bool
    {
        $nameMin = $params['nameMin'];
        $nameMax = $params['nameMax'];

        if (!empty($values[$nameMin]))
        {
            assert(is_string($values[$nameMin]));

            if (!empty($values[$nameMax]))
            {
                assert(is_string($values[$nameMax]));

                if ($values[$nameMin] === $values[$nameMax])
                {
                    $conditions[] = $this->_createCondition(sprintf("%s = ?", $fieldName), [$values[$nameMin]]);
                    return true;
                }

                $conditions[] = $this->_createCondition(sprintf("%s BETWEEN ? AND ?", $fieldName), [$values[$nameMin], $values[$nameMax]]);
                return true;
            }

            $conditions[] = $this->_createCondition(sprintf("%s >= ?", $fieldName), [$values[$nameMin]]);
            return true;
        }

        if (!empty($values[$nameMax]))
        {
            assert(is_string($values[$nameMax]));

            $conditions[] = $this->_createCondition(sprintf("%s <= ?", $fieldName), [$values[$nameMax]]);
            return true;
        }

        return false;
    }

    ##################################################################################

    /**
     * Создаётся условие для конкретного элемента фильтра.
     *
     * @param      string  $value
     * @param      array|null  $bind [string => mixed, ...]
     * @return     string
     */
    protected function _createCondition(string $value, array $bind = null): string
    {
        return (new HelperExpression($value, $bind))->get($this->db);
    }

}