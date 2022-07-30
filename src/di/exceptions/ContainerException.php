<?php declare(strict_types=1);
namespace mrcore\di\exceptions;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Exception: класс для формирования информации об ошибках связанной с HTTP.
 *
 * @author  Andrey J. Nazarov
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface
{

    public static function configNotFound(string $id, string $containerClass): self
    {
        return new self
        (
            sprintf
            (
                'Entry class %s is not associated with any configuration file in container %s',
                $id, $containerClass
            )
        );
    }

}