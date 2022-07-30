<?php declare(strict_types=1);
namespace mrcore\exceptions;
use mrcore\http\ResponseInterface;
use RuntimeException;

/**
 * Exception: класс для формирования информации об ошибках связанной с HTTP.
 *
 * @author  Andrey J. Nazarov
 */
class HttpException extends RuntimeException
{

    public static function notFoundIncompleteProcess(string $actionClass, string $residuePath): self
    {
        return new self
        (
            'Resource is not found',
            ResponseInterface::HTTP_NOT_FOUND,
            new RuntimeException
            (
                sprintf
                (
                    'Метод run() класса %s не до конца обработал значения "%s" в $pathToAction->residuePath',
                    $actionClass, $residuePath
                )
            )
        );
    }

    public static function isNotFound(string $message = null): self
    {
        if (null === $message)
        {
            $message = 'Not Found';
        }

        return new self
        (
            $message,
            ResponseInterface::HTTP_NOT_FOUND
        );
    }

    public static function fileIsNotFound(string $filePath): self
    {
        return new self
        (
            'File is not found',
            ResponseInterface::HTTP_NOT_FOUND,
            new RuntimeException
            (
                sprintf
                (
                    'File %s is not found',
                    $filePath
                )
            )
        );
    }

}