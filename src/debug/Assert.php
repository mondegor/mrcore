<?php declare(strict_types=1);
namespace mrcore\debug;
use ReflectionClass;

/**
 * Проверка часто встречаемых утверждений.
 *
 * @author  Andrey J. Nazarov
 */
class Assert
{
    /**
     * Проверяется, что указанное значение попадает в указанный интервал целых чисел.
     */
    public static function isInt($value, int $min = null, int $max = null): bool
    {
        return is_int($value) && (null === $min || $value >= $min) && (null === $max || $value <= $max);
    }

    /**
     * Возвращается сообщение, если указанное значение не попадает в указанный интервал целых чисел.
     */
    public static function isIntMessage($value, int $min = null, int $max = null): string
    {
        if (self::isInt($value))
        {
            return '';
        }

        if (!is_int($value))
        {
            return sprintf('The value %s must be an integer', $value);
        }

        if (null === $min)
        {
            return sprintf('The value %d must be no more than %d', $value, $max);
        }

        if (null === $max)
        {
            return sprintf('The value %d must be no less than %d', $value, $min);
        }

        return sprintf('The value %d must be between %d and %d', $value, $min, $max);
    }

    /**
     * Проверяется, что класс $objectOrClass наследуется от класса $class.
     */
    public static function instanceOf(mixed $objectOrClass, string $class): bool
    {
        return is_subclass_of($objectOrClass, $class);
    }

    /**
     * Возвращается сообщение, если класс $objectOrClass не наследуется от класса $class.
     */
    public static function instanceOfMessage(mixed $objectOrClass, string $class): string
    {
        if (is_subclass_of($objectOrClass, $class))
        {
            return '';
        }

        $classType = 'class';
        $reflection = new ReflectionClass($class);

        if ($reflection->isInterface())
        {
            $classType = 'interface';
        }
        else if ($reflection->isAbstract())
        {
            $classType = 'abstract class';
        }

        if (is_object($objectOrClass))
        {
            $objectOrClass = get_class($objectOrClass);
        }

        return sprintf('The class %s is not an instance of the %s %s', $objectOrClass, $classType, $class);
    }

}