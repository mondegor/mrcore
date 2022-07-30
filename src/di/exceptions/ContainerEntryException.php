<?php declare(strict_types=1);
namespace mrcore\di\exceptions;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * Exception: класс для формирования информации об ошибках связанной с HTTP.
 *
 * @author  Andrey J. Nazarov
 */
class ContainerEntryException extends RuntimeException implements NotFoundExceptionInterface
{

    public static function entryNotFound(string $id, string $containerClass): self
    {
        return new self
        (
            sprintf('Entry %s is not found in container %s', $id, $containerClass)
        );
    }

}