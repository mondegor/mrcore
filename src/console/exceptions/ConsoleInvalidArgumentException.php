<?php declare(strict_types=1);
namespace mrcore\console\exceptions;
use InvalidArgumentException;

/**
 * Исключения связанные с классом {@see ConsoleArgs}.
 *
 * @author  Andrey J. Nazarov
 */
class ConsoleInvalidArgumentException extends InvalidArgumentException
{

    public static function argumentIsInvalid(string $value): self
    {
        return new self
        (
            sprintf('The argument "%s" is invalid', $value)
        );
    }

    public static function requiredOptionMissing(string $name): self
    {
        return new self
        (
            sprintf('The required option "%s%s" is missing', self::_getOptionPrefix($name), $name)
        );
    }

    public static function requiredArgumentForOptionMissing(string $name): self
    {
        return new self
        (
            sprintf('A required argument for the option "%s%s" is missing', self::_getOptionPrefix($name), $name)
        );
    }

    public static function optionNotAllowedArgument(string $name): self
    {
        return new self
        (
            sprintf('The option "%s%s" is not allowed to have an argument', self::_getOptionPrefix($name), $name)
        );
    }

    public static function optionNotFoundInSchema(string $name): self
    {
        return new self
        (
            sprintf('The option "%s%s" is not found in the option schema', self::_getOptionPrefix($name), $name)
        );
    }

    /**
     * Возвращается "-" или "--" в зависимости от заданного формата опции.
     */
    protected static function _getOptionPrefix(string $name)
    {
        return strlen($name) > 1 ? '--' : '-';
    }

}