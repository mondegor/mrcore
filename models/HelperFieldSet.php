<?php declare(strict_types=1);
namespace mrcore\models;
use Closure;
use mrcore\db\HelperExpr;
use mrcore\exceptions\DbException;
use mrcore\services\VarService;

require_once 'mrcore/db/HelperExpr.php';
require_once 'mrcore/services/VarService.php';

/**
 * Вспомогательный класс для корректной установки
 * значений полей перед их сохранением в БД.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/db
 */
class HelperFieldSet
{
    /**
     * Массив зарегистрированных полей модели объекта.
     *
     * @var    array (см. AbstractModel::$_fields)
     */
    private array $_fields;

    /**
     * Массив в который записываются значения полей.
     *
     * @var    array [string, ...]
     */
    private array $_set = [];

    /**
     * Массив из которого берутся значения полей.
     * @var    array [string => mixed, ...]
     */
    private array $_props;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      array  $fields (см. AbstractModel::$_fields)
     * @param      array  $props OPTIONAL [string => mixed, ...]
     */
    public function __construct(array &$fields, array $props = [])
    {
        $this->_fields = &$fields;
        $this->_props = &$props;
    }

    /**
     * Добавление поля в HelperFieldSet (для INSERT) с экранированием его значения при условии,
     * что оно существует в массиве $this->_props, иначе будет выдано предупреждение.
     *
     * @param      string  $name
     * @param      mixed  $default OPTIONAL
     * @param      Closure  $cbValue OPTIONAL (function ($value): string)
     * @return     HelperFieldSet
     * @throws     DbException
     */
    public function &add(string $name, $default = null, Closure $cbValue = null): HelperFieldSet
    {
        if (!empty($this->_fields[$name]['readonly']))
        {
            require_once 'mrcore/exceptions/DbException.php';
            throw new DbException(sprintf('The field "%s" is readonly', $name));
        }

        $isNull = $this->_isNull($name);
        $isEmptyToNull = $isNull && $this->_isEmptyToNull($name);

        // если в поле указано NULL и оно не может принимать null значения, то выводится предупреждение
        if (!isset($this->_props[$name]) && !$isNull)
        {
            if (array_key_exists($name, $this->_props))
            {
                require_once 'mrcore/exceptions/DbException.php';
                throw new DbException(sprintf('Элемент "%s" в массиве $this->_props равен NULL, но поле "%s" БД не может принимать NULL значения', $name, $this->dbName($name)));
            }

            if (null === $default)
            {
                require_once 'mrcore/exceptions/DbException.php';
                throw new DbException(sprintf('Элемент массива "%s" отсутствует, и для него не было указано значение по умолчанию для вставки в поле "%s" БД', $name, $this->dbName($name)));
            }
        }

        $value = array_key_exists($name, $this->_props) ? $this->_props[$name] : $default;

        if ($cbValue instanceof Closure)
        {
            $value = $cbValue($value);
        }

        if ($value instanceof HelperExpr)
        {
            $this->_set[$name] = $value;
            return $this;
        }

        $type = $this->_dbType($name);

        if (null !== $value)
        {
            $value = VarService::cast($type, $value, true);
        }

        switch ($type)
        {
            case VarService::T_INT:
            case VarService::T_FLOAT:
                $this->_set[$name] = $this->_getDbValue($value, 0, $isNull, false);
                break;

            case VarService::T_STRING:
                if (!empty($this->_fields[$name]['length']) &&
                        is_string($value) && mb_strlen($value) > $this->_fields[$name]['length'])
                {
                    $value = mb_substr($value, 0, $this->_fields[$name]['length']);

                    trigger_error(sprintf('The value of the field %s has been truncated to %u characters',
                                              $name, $this->_fields[$name]['length']), E_USER_NOTICE);
                }

                $this->_set[$name] = $this->_getDbValue($value, '', $isNull, $isEmptyToNull);
                break;

            case VarService::T_DATETIME:
            case VarService::T_DATE:
            case VarService::T_IP:
            case VarService::T_TIME:
            case VarService::T_TIMESTAMP:
                $this->_set[$name] = $this->_getDbValue($value, '', $isNull, $isEmptyToNull);
                break;

            case VarService::T_BOOL:
                if (null !== $value)
                {
                    $value = (int)$value;
                }

                $this->_set[$name] = $this->_getDbValue($value, 0, $isNull, false);
                break;

            case VarService::T_ENUM:
                // значение в ENUM должно быть не пустой строкой (или если это число, то больше нуля) или
                // быть NULL (если разрешено сохранять NULL значения). Пустые значения здесь не сохраняются.
                // $enumList = $this->_enumList($name);
                $this->_set[$name] = $this->_getDbValue($value, '', $isNull, $isEmptyToNull);
                break;

            case VarService::T_ARRAY:
                if (null !== $value)
                {
                    $value = empty($value) ? '' : json_encode($value, JSON_THROW_ON_ERROR, 512);
                }

                $this->_set[$name] = $this->_getDbValue($value, '', $isNull, $isEmptyToNull);
                break;

            case VarService::T_ESET:
                // :TODO: $value < pow(2, count($enumList))
                // $enumList = $this->_enumList($name);

                if (is_array($value))
                {
                    $value = empty($value) ? '' : implode(',', $value);
                }

                $this->_set[$name] = $this->_getDbValue($value, '', $isNull, $isEmptyToNull);
                break;

            case VarService::T_IPLONG:
                $this->_set[$name] = $this->_getDbValue($value, 0, $isNull, $isEmptyToNull);
                break;

            default:
                require_once 'mrcore/exceptions/DbException.php';
                throw new DbException(sprintf('Задан неизвестный тип данных "%s"', $this->_dbType($name)));
                break;
        }

        return $this;
    }

    /**
     * Прямое добавление значения в HelperFieldSet для INSERT (без экранирования).
     * При $checkExists = false допускается задавать любое название поля таблицы,
     * которое не обязательно должно быть зарегистрированно в источнике.
     *
     * @param      string  $name
     * @param      mixed  $value
     * @param      bool  $checkExists OPTIONAL
     * @return     HelperFieldSet
     * @throws     DbException
     */
    public function &addValue(string $name, $value, bool $checkExists = true): HelperFieldSet
    {
        if ($checkExists && !array_key_exists($name, $this->_set))
        {
            require_once 'mrcore/exceptions/DbException.php';
            throw new DbException(sprintf('Field "%s" is not registered in $this->_fields', $name));
        }

        $this->_set[$name] = $value;

        return $this;
    }

    /**
     * Добавление поля в HelperFieldSet (для UPDATE) с экранированием его значения при условии,
     * что оно существует в массиве $this->_props.
     *
     * @param      string  $name
     * @param      Closure  $cbValue OPTIONAL (function ($value): string)
     * @return     HelperFieldSet
     * @throws     DbException
     */
    public function &set(string $name, Closure $cbValue = null): HelperFieldSet
    {
        if (isset($this->_props[$name]))
        {
            return $this->add($name, null, $cbValue);
        }

        // если в значении указано NULL
        if (array_key_exists($name, $this->_props))
        {
            // если поле не может принимать null значения, то выводится предупреждение
            if (!$this->_isNull($name))
            {
                require_once 'mrcore/exceptions/DbException.php';
                throw new DbException(sprintf('Элемент "%s" в массиве $this->_props равен NULL, но поле "%s" БД не может принимать NULL значения', $name, $this->dbName($name)));
            }

            $this->_set[$name] = new HelperExpr('NULL');
        }

        return $this;
    }

    /**
     * Прямое обновление значения в HelperFieldSet для UPDATE (без экранирования).
     * При $checkExists = false допускается задавать любое название поля таблицы,
     * которое не обязательно должно быть зарегистрированно в источнике.
     *
     * @param      string  $name
     * @param      mixed  $value
     * @param      bool   $checkExists OPTIONAL
     * @return     HelperFieldSet
     * @throws     DbException
     */
    public function &setValue(string $name, $value, bool $checkExists = true): HelperFieldSet
    {
        return $this->addValue($name, $value, $checkExists);
    }

    /**
     * Возвращение установленного значения в HelperFieldSet.
     *
     * @param      string  $name
     * @param      bool    $checkExists
     * @return     mixed
     * @throws     DbException
     */
    public function getValue(string $name, bool $checkExists = true)
    {
        if (!array_key_exists($name, $this->_set))
        {
            if ($checkExists)
            {
                 throw new DbException(sprintf('Field "%s" is not registered in $this->_set', $name));
            }

            return null;
        }

        return $this->_set[$name];
    }

    /**
     * Проверяется является ли HelperFieldSet пустым.
     *
     * @return     bool
     */
    public function isEmpty(): bool
    {
        return empty($this->_set);
    }

    /**
     * По названию поля модельного объекта возвращается название поля БД.
     *
     * @param      string  $name
     * @return     string
     */
    public function dbName(string $name): string
    {
        if (!empty($this->_fields[$name]['dbName']))
        {
            $name = $this->_fields[$name]['dbName'];

            if (false !== ($index = strpos($name, '.')))
            {
                // preg_replace('/([_a-z][_a-z0-9]*\.)([_a-zA-Z][_a-zA-Z0-9]*)/u', '$2', $name)
                $name = substr($name, $index + 1);
            }
        }

        return $name;
    }

    /**
     * Возвращение текущего состояния HelperFieldSet.
     *
     * @return     array [string => mixed, ...]
     */
    public function result(): array
    {
        $result = [];

        foreach ($this->_set as $name => $value)
        {
            $result[$this->dbName($name)] = $value;
        }

        return $result;
    }

    /**
     * По названию поля модельного объекта возвращается его тип в БД.
     *
     * @param      string  $name
     * @return     int
     */
    protected/*__private__*/ function _dbType(string $name): int
    {
        return $this->_fields[$name]['type'] ?? VarService::T_STRING;
    }

    /**
     * Может ли указаное поле записывать NULL значения.
     *
     * @param      string  $name
     * @return     bool
     */
    protected/*__private__*/ function _isNull($name): bool
    {
        return !empty($this->_fields[$name]['null']);
    }

    /**
     * Можно ли пустое значение указаного поля преобразовывать в NULL значение.
     *
     * @param      string  $name
     * @return     bool
     */
    protected/*__private__*/ function _isEmptyToNull(string $name): bool
    {
        return !isset($this->_fields[$name]['emptyToNull']) || $this->_fields[$name]['emptyToNull'];
    }

    ///**
    // * Получение списка возможных полей для ENUM и ESET.
    // *
    // * @param      string  $name
    // * @return     array [string, ...]
    // */
    //protected/*__private__*/ function _enumList(string $name): array
    //{
    //    return empty($this->_fields[$name]['enum']) ? [] : $this->_fields[$name]['enum'];
    //}

    /**
     * Получение значения переменной, если она не пустая,
     * иначе происходит попытка преобразовать её $zero или NULL значению.
     *
     * @param    mixed  $value
     * @param    mixed  $zero
     * @param    bool   $isNull
     * @param    bool   $isEmptyToNull
     * @return   mixed
     */
    protected/*__private__*/ function _getDbValue($value, $zero, bool $isNull, bool $isEmptyToNull)
    {
        if ($zero !== $value && null !== $value)
        {
            return $value;
        }

        return ($isNull && ($isEmptyToNull || null === $value) ? new HelperExpr('NULL') : $zero);
    }

}