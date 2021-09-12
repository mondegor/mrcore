<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\exceptions\DbException;

require_once 'mrcore/services/InterfaceInjectableService.php';

/**
 * Доступ к переменным из $_REQUEST и $_COOKIE.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/services
 * @uses       $_REQUEST
 * @uses       $_COOKIE
 */
class VarService implements InterfaceInjectableService
{
    /**
     * Типы данных.
     *
     * @const    int
     */
    public const T_BOOL      = 0,
                 T_INT       = 1,
                 T_FLOAT     = 2,
                 T_TIME      = 3,
                 T_DATE      = 4,
                 T_DATETIME  = 5,
                 T_TIMESTAMP = 6,
                 T_STRING    = 7,
                 T_ENUM      = 8,
                 T_ESET      = 9,
                 T_ARRAY     = 10,
                 T_ANY       = 50,  // переменная без конкретного типа
                 T_IP        = 100, // ip в виде строки
                 T_IPLONG    = 101; // ip в виде целого числа

    /**
     * Шаблоны для проверки корректности данных.
     *
     * @const    string
     */
    public const PATTERN_DATE      = "^\d{4}-[01]\d-[0-3]\d$",
                 PATTERN_TIME      = "^[0-2]\d:[0-5]\d:[0-5]\d$",
                 PATTERN_DATETIME  = "^\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d$",
                 PATTERN_TIMESTAMP = "^\d{4}[01]\d[0-3]\d[0-2]\d[0-5]\d[0-5]\d$",
                 PATTERN_IP        = "^[0-2]?\d{2,3}\.[0-2]?\d{2,3}\.[0-2]?\d{2,3}\.[0-2]?\d{2,3}$";

    /**
     * Соответствие шаблона указанному типу.
     *
     * @const    array [int => string, ...]
     */
    public const PATTERNS = array
    (
        self::T_DATE => self::PATTERN_DATE,
        self::T_TIME => self::PATTERN_TIME,
        self::T_DATETIME => self::PATTERN_DATETIME,
        self::T_TIMESTAMP => self::PATTERN_TIMESTAMP,
        self::T_IP => self::PATTERN_IP,
        self::T_IPLONG => self::PATTERN_IP
    );

    /**
     * Используется в методе self::convert().
     *
     * @const    string
     */
    private const IN_CHARSET = 'cp1251',
                  OUT_CHARSET = 'utf-8';

    #################################### Methods #####################################

    /**
     * Приведение указанного значения к указанному типу.
     *
     * @param      int  $type
     * @param      mixed  $value
     * @param      bool  $throwIfHard
     * @return     mixed
     * @throws     DbException
     */
    public static function cast(int $type, $value, bool $throwIfHard = false)
    {
        while (is_array($value))
        {
            if (self::T_ARRAY === $type)
            {
                return $value;
            }

            if (self::T_ESET === $type)
            {
                $value = array_map('trim', array_filter
                (
                    $value,
                    static function ($item)
                    {
                        return is_string($item) && '' !== trim($item);
                    }
                ));

                return $value;
            }

            $value = empty($value) ? null : array_shift($value);
        }

        ##################################################################################

        if (is_string($value) && '' !== $value &&
                isset(self::PATTERNS[$type]) &&
                    preg_match('/' . self::PATTERNS[$type] . '/i', $value) <= 0)
        {
            if ($throwIfHard)
            {
                require_once 'mrcore/exceptions/DbException.php';
                throw new DbException(sprintf('The value of "%s" does not match the pattern of its type %s. Value set EMPTY', $value, $type));
            }

            $value = '';
        }

        switch ($type)
        {
            case self::T_INT:
                $value = (int)$value;
                break;

            case self::T_STRING:
                $value = (string)$value;
                break;

            case self::T_FLOAT:
                $value = (float)(is_string($value) ? strtr($value, ',', '.') : $value);
                break;

            case self::T_BOOL:
                $value = (bool)$value;
                break;

            case self::T_ENUM:
                $value = is_string($value) ? trim($value) : ($value > 0 ? (int)$value : '');
                break;

            case self::T_DATETIME:
            case self::T_DATE:
            case self::T_TIME:
            case self::T_TIMESTAMP:
                $value = is_string($value) ? $value : '';
                break;

            case self::T_ARRAY:
                $value = ('' === $value ? [] : (array)$value);
                break;

            case self::T_ESET:
                // если передан массив в виде строки (значения идущие через запятую)
                $value = is_string($value) ? array_filter(array_map('trim', explode(',', $value))) : ($value > 0 ? (int)$value : []);
                break;

            case self::T_IP:
                // если IP адрес находится в виде числа, то он переводится в IP
                $value = is_string($value) ? (string)$value : ($value > 0 ? long2ip((int)$value) : '');
                break;

            case self::T_IPLONG:
                // если IP адрес задан в виде *.*.*.*, то он переводится в число
                $value = is_string($value) ? (int)ip2long($value) : ($value > 0 ? (int)$value : 0);
                break;

            default:
                trigger_error(sprintf('Unknown type %s', $type), E_USER_NOTICE);
                break;
        }

        return $value;
    }

    /**
     * Корректировка строки присланной из внешнего окружения.
     *
     * @param      mixed  $value
     * @return     mixed
     */
    public static function convert($value)
    {
        self::_convert($value);

        return $value;
    }

    /**
     * Корректировка строки присланной из внешнего окружения.
     *
     * @param      mixed  $value
     */
    private static function _convert(&$value): void
    {
        if (is_array($value))
        {
            foreach ($value as &$_value)
            {
                self::_convert($_value);
            }
        }
        else if (is_string($value))
        {
            if (!preg_match('//u', $value))
            {
                $value = iconv(self::IN_CHARSET, self::OUT_CHARSET, $value);
            }

            $value = trim($value);
        }
    }

    ##################################################################################

    /**
     * Возвращает STRING значение параметра $name из $_REQUEST,
     * либо значение по умолчанию $default.
     *
     * @param      string  $name
     * @param      string  $default OPTIONAL
     * @return     string
     */
    public function get(string $name, string $default = ''): string
    {
        if (!isset($_REQUEST[$name]))
        {
            return $default;
        }

        return $this->_wrapConvert($this->_wrapCast(self::T_STRING, $_REQUEST[$name]));
    }

    /**
     * Возвращает INT значение параметра $name из $_REQUEST,
     * либо значение по умолчанию $default (если указано).
     *
     * @param      string  $name
     * @param      int  $default OPTIONAL
     * @return     int
     */
    public function getInt(string $name, int $default = 0): int
    {
        if (!isset($_REQUEST[$name]))
        {
            return $default;
        }

        return $this->_wrapCast(self::T_INT, $_REQUEST[$name]);
    }

    /**
     * Возвращает FLOAT значение параметра $name из $_REQUEST,
     * либо значение по умолчанию $default (если указано).
     *
     * @param      string  $name
     * @param      float  $default OPTIONAL
     * @return     float
     */
    public function getFloat(string $name, float $default = 0.0): float
    {
        if (!isset($_REQUEST[$name]))
        {
            return $default;
        }

        return $this->_wrapCast(self::T_FLOAT, $_REQUEST[$name]);
    }

    /**
     * Возвращает ARRAY значение параметра $name из $_REQUEST,
     * либо значение по умолчанию $default (если указано).
     *
     * @param      string  $name
     * @param      array  $default OPTIONAL
     * @return     array
     */
    public function getArray(string $name, array $default = []): array
    {
        if (!isset($_REQUEST[$name]))
        {
            return $default;
        }

        return $this->_wrapConvert($this->_wrapCast(self::T_ARRAY, $_REQUEST[$name]));
    }

    /**
     * Возвращает COOKIE значение параметра $name из $_COOKIE.
     *
     * @param      string $name
     * @return     string
     */
    public function cookie(string $name): string
    {
        if (!isset($_COOKIE[$name]))
        {
            return '';
        }

        return $this->_wrapConvert($this->_wrapCast(self::T_STRING, $_COOKIE[$name]));
    }

    /**
     * @see    VarService::cast()
     */
    /*__private__*/protected function _wrapCast(int $type, $value)
    {
        return self::cast($type, $value);
    }

    /**
     * @see    VarService::convert()
     */
    /*__private__*/protected function _wrapConvert($value)
    {
        return self::convert($value);
    }

}