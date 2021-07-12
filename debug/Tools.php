<?php declare(strict_types=1);
namespace mrcore\debug;
use ReflectionObject;

/**
 * Набор методов используемых при отладке программы.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore.debug
 */
/*__class_static__*/ final class Tools
{
    /**
     * Преобразование переменной в текстовое представление.
     * Длинные строки и массивы с большим количестовм элементов будут усечены.
     *
     * @param      mixed  $variable (false, 1, 1.23, [0 => 1], ...)
     * @param      int    $number OPTIONAL
     * @param      int    $maxDeep - защита от ссылок в массивах
     * @return     string
     */
    public static function var2str($variable, int $number = 0, int $maxDeep = 8): string
    {
        $mapping = ['boolean' => 'bool', 'double' => 'float', 'integer' => 'int'];
        $type = gettype($variable);

        if (isset($mapping[$type]))
        {
            $type = $mapping[$type];
        }

        switch ($type)
        {
            case 'object':
                $result = 'object(' . count((new ReflectionObject($variable))->getProperties())  . ') "' . get_class($variable) . '"';
                break;

            case 'array':
                $result = 'array(0)';

                if (($cnt = count($variable)) > 0)
                {
                    $isIndexed = true;
                    $i = 0;

                    foreach ($variable as $key => $value)
                    {
                        if ($i++ !== $key)
                        {
                            $isIndexed = false;
                            break;
                        }
                    }

                    $values = '';
                    $i = 1;

                    foreach ($variable as $key => $value)
                    {
                        if ($i++ > 10)
                        {
                            $values .= '...  ';
                            break;
                        }

                        $values .= ($isIndexed ? '' : $key . ' => ') . ($maxDeep > 0 ? self::var2str($value, 0, $maxDeep - 1) : '[MAX_DEEP]') . ', ';
                    }

                    $result = sprintf('array(%u) {%s}', $cnt, substr($values, 0, -2));
                }
                break;

            case 'bool':
                $result = sprintf('%s(%s)', $type, $variable ? 'true' : 'false');
                break;

            case 'int':
            case 'float':
            case 'resource':
                $result = sprintf('%s(%s)', $type, $variable);
                break;

            case 'NULL':
                $result = 'NULL';
                break;

            case 'string':
                $length = mb_strlen($variable);

                if ($length > 40 + 3)
                {
                    $variable = mb_substr($variable, 0, 32) . '...' . mb_substr($variable, $length - 8);
                }

                if ('' === $variable)
                {
                    $result = 'string(0)';
                }
                else
                {
                    $result = '"' . str_replace(["\n\r", "\n", "\r"], ['\n\r', '\n', '\r'], $variable) . '"';
                    $result = 'string(' . $length . ') ' . $result;
                }
                break;

            default:
                $result = var_export($variable, true);
                break;
        }

        ##################################################################################

        if ($number > 0)
        {
            $result = '$arg' . $number . ' = ' . $result;
        }

        return $result;
    }

    /**
     * Перевод указанных аргументов (функции, метода) в текстовое представление.
     * Длинные строки и массивы с большим количестовм элементов будут усечены.
     *
     * @param      array  $args [string => mixed, ...]
     * @return     string
     */
    public static function args2str(array $args): string
    {
        $result = '';

        if (!empty($args))
        {
            $number = 1;

            foreach ($args as $arg)
            {
                $result .= self::var2str($arg, $number++) . ', ';
            }

            $result = substr($result, 0, -2);
        }

        return $result;
    }

    /**
     * Скрытие данных у элементов массива, по фразам встречаемых в названиях ключей
     * и возвращение массива.
     *
     * @param      array  $data [string => mixed, ...]
     * @param      array  $words [string, ...]
     * @return     array
     */
    public static function getHiddenData(array $data, array $words): array
    {
        self::hideData($data, $words);

        return $data;
    }

    /**
     * Скрытие данных у элементов массива, по фразам встречаемых в названиях ключей.
     *
     * @param      array  $data [string => mixed, ...]
     * @param      array  $words [string, ...]
     * @param      int    $maxDeep - защита от ссылок в массивах
     * @return     bool
     */
    public static function hideData(array &$data, array $words, int $maxDeep = 8): bool
    {
        if (empty($data) || empty($words))
        {
            return false;
        }

        $isChanged = false;

        foreach ($data as $key => &$item)
        {
            if (is_array($item))
            {
                if ($maxDeep > 0 && self::hideData($item, $words, $maxDeep - 1))
                {
                    $isChanged = true;
                }
            }
            else if (is_string($item))
            {
                foreach ($words as $word)
                {
                    if (is_string($key) && false !== mb_stripos($key, $word))
                    {
                        $item = '***hidden***';
                        $isChanged = true;
                    }
                }
            }
        }

        return $isChanged;
    }

    ///**
    // * Формирование в текстовый вид указанного аргумента.
    // * Массивы и объекты формируются с помощью var_export.
    // *
    // * @param      mixed  $arg
    // * @param      int  $number
    // * @return     string
    // */
    //private static function _arg($arg, int $number): string
    //{
    //    $result = MRCORE_LINE_DASH . $number . '. ';
    //
    //    if (null === $arg)
    //    {
    //        $result .= 'null';
    //    }
    //    else if (is_bool($arg))
    //    {
    //        $result .= 'bool ' . ($arg ? 'true' : 'false');
    //    }
    //    else if (is_int($arg))
    //    {
    //        $result .= 'int ' . $arg;
    //    }
    //    else if (is_float($arg))
    //    {
    //        $result .= 'float ' . $arg;
    //    }
    //    else if (is_string($arg))
    //    {
    //        $result .= 'string \'' . $arg . '\' (length=' . mb_strlen($arg) . ')';
    //    }
    //    else if (is_array($arg))
    //    {
    //        $result .= var_export($arg, true) . ';';
    //    }
    //    else if (is_object($arg))
    //    {
    //        $result .= 'object(' . get_class($arg). ')' . PHP_EOL . var_export($arg, true) . ';';
    //    }
    //    else
    //    {
    //        $result .= gettype($arg) . ' ' . $arg;
    //    }
    //
    //    return $result;
    //}

}