<?php declare(strict_types=1);
namespace mrcore\exceptions;
use Exception;
use RuntimeException;

/**
 * Exception: контейнер для передачи аргументов добавленых в момент исключения.
 *
 * @author  Andrey J. Nazarov
 */
class ArgsException extends RuntimeException
{
    /**
     * Аргументы объекта исключения.
     *
     * @var  array [string => mixed, ...]
     */
    private array $args;

    #################################### Methods #####################################

    /**
     * @param  array  $args {@see ArgsException::$args}
     */
    public function __construct(string $message, array $args, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->args = $args;
    }

    /**
     * Возвращаются аргументы исключения.
     *
     * @return array {@see ArgsException::$args}
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Возвращается аргумент по указанному ключу.
     */
    public function get(string $key): mixed
    {
        return $this->args[$key] ?? null;
    }

}