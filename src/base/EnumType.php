<?php declare(strict_types=1);
namespace mrcore\base;
use RuntimeException;

/**
 * Определение, конвертация, приведение различных типов переменных.
 *
 * @author  Andrey J. Nazarov
 */
class EnumType
{
    /**
     * Типы переменных.
     */
    public const NULL      = 0,
                 BOOL      = 1,
                 INT       = 2,
                 FLOAT     = 3,
                 TIME      = 4,
                 DATE      = 5,
                 DATETIME  = 6,
                 STRING    = 7,
                 ENUM      = 8,
                 ESET      = 9,
                 ARRAY     = 10,
                 IP        = 11,
                 IPLONG    = 12;

    /**
     * Соответствие типу переменной её название.
     *
     * @var    array [int => string, ...]
     */
    public const NAMES = array
    (
        self::NULL      => 'NULL',
        self::BOOL      => 'BOOL',
        self::INT       => 'INT',
        self::FLOAT     => 'FLOAT',
        self::TIME      => 'TIME',
        self::DATE      => 'DATE',
        self::DATETIME  => 'DATETIME',
        self::STRING    => 'STRING',
        self::ENUM      => 'ENUM',
        self::ESET      => 'ESET',
        self::ARRAY     => 'ARRAY',
        self::IP        => 'IP',
        self::IPLONG    => 'IPLONG'
    );

    /**
     * Шаблоны для проверки корректности данных.
     */
    public const PATTERN_DATE     = "^\d{4}-[01]\d-[0-3]\d$",
                 PATTERN_TIME     = "^[0-2]\d:[0-5]\d:[0-5]\d$",
                 PATTERN_DATETIME = "^\d{4}-[01]\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d$",
                 PATTERN_IP       = "^[0-2]?\d{1,2}\.[0-2]?\d{1,2}\.[0-2]?\d{1,2}\.[0-2]?\d{1,2}$";

    /**
     * Соответствие шаблона указанному типу.
     *
     * @var    array [int => string, ...]
     */
    public const PATTERNS = array
    (
        self::DATE     => self::PATTERN_DATE,
        self::TIME     => self::PATTERN_TIME,
        self::DATETIME => self::PATTERN_DATETIME,
        self::IP       => self::PATTERN_IP
    );

    /**
     * Используется в методе {@link EnumType::convert()}.
     */
    private const IN_CHARSET = 'cp1251',
                  OUT_CHARSET = 'utf-8';

    #################################### Methods #####################################

    /**
     * Приведение указанного значения к указанному типу.
     */
    public static function cast(int $type, string|int|float|bool|array|null $value, bool $throwIfHard = false): string|int|float|bool|array|null
    {
        while (is_array($value))
        {
            if (self::ARRAY === $type)
            {
                return $value;
            }

            if (self::ESET === $type)
            {
                return array_map
                (
                    'trim',
                    array_filter
                    (
                        $value,
                        static function ($item): bool
                        {
                            return is_string($item) && '' !== trim($item);
                        }
                    )
                );
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
                throw new RuntimeException(sprintf('The value of "%s" does not match the pattern of its type %s. Value set EMPTY', $value, $type));
            }

            $value = '';
        }

        switch ($type)
        {
            case self::INT:
                $value = (int)$value;
                break;

            case self::STRING:
                $value = (string)$value;
                break;

            case self::FLOAT:
                $value = (float)(is_string($value) ? strtr($value, ',', '.') : $value);
                break;

            case self::BOOL:
                $value = (bool)$value;
                break;

            case self::ENUM:
                $value = is_string($value) ? trim($value) : '';
                break;

            case self::DATETIME:
            case self::DATE:
            case self::TIME:
                $value = is_string($value) ? $value : '';
                break;

            case self::ARRAY:
                $value = ('' === $value ? [] : (array)$value);
                break;

            case self::ESET:
                // если передан массив в виде строки (значения идущие через запятую)
                $value = is_string($value) ? array_filter(array_map('trim', explode(',', $value))) : [];
                break;

            case self::IP:
                // если IP адрес находится в виде числа, то он переводится в IP
                $value = is_string($value) ? $value : ($value > 0 ? long2ip((int)$value) : '');
                break;

            case self::IPLONG:
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
     */
    public static function convert(string|int|float|bool|array|null $value): string|int|float|bool|array|null
    {
        self::_convert($value);

        return $value;
    }

    /**
     * Корректировка строки присланной из внешнего окружения.
     */
    protected static function _convert(string|int|float|bool|array|null &$value): void
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

}