<?php declare(strict_types=1);
namespace mrcore\exceptions;
use RuntimeException;

/**
 * Exception: класс для формирования информации об ошибках связанной с MrCore.
 *
 * @author  Andrey J. Nazarov
 */
class CoreException extends RuntimeException
{

    public static function actionCallLimitExceeded(string $actionClass, string $generatedActionClass): self
    {
        return new self
        (
            sprintf
            (
                'Превышено кол-во рекурентных вызовов обработчиков породивших экшеном "%s", последний неудачный вызов произвёл экшен "%s"',
                $generatedActionClass, $actionClass
            )
        );
    }

    public static function responseContentIsAllReadyExists(string $content): self
    {
        return new self
        (
            sprintf
            (
                'Тело ответа "%s" уже сформировано ранее в виде строки, поэтому преобразование в массив невозможно',
                $content
            )
        );
    }

    public static function httpStatusCodeIsNotRedirect(int $statusCode): self
    {
        return new self
        (
            sprintf
            (
                'The HTTP status code is not a redirect ("%s" given).',
                $statusCode
            )
        );
    }

}