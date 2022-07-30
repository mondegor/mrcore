<?php declare(strict_types=1);
namespace mrcore\exceptions;
use RuntimeException;

/**
 * Exception: класс для формирования информации об ошибках связанной с MrCore.
 *
 * @author  Andrey J. Nazarov
 */
class ViewException extends RuntimeException
{

    public static function templateIsNotFound(string $name): self
    {
        return new self
        (
            sprintf
            (
                'Template %s is not found or incorrect', $name
            )
        );
    }

    public static function templateStyleSheetIsIncorrect(string $name): self
    {
        return new self
        (
            sprintf
            (
                'StyleSheet of template %s is or incorrect', $name
            )
        );
    }

}