<?php declare(strict_types=1);
namespace mrcore\lib;

/**
 * Библиотека объединяющая методы генерации
 * строковых последовательностей и шифрования данных.
 *
 * @author  Andrey J. Nazarov
 */
/*__class_static__*/ class Str
{
    function stripEnd(string $name, string $char, int $count = 1): string|false
    {
        assert($count > 0);

        do
        {
            if (false === ($index = strrpos($name, $char)))
            {
                return false;
            }

            $name = substr($name, 0, $index);
        }
        while (--$count > 0);

        return $name;
    }
}