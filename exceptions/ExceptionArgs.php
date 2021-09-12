<?php declare(strict_types=1);
namespace mrcore\exceptions;
use Exception;

/**
 * Exception: контейнер для передачи аргументов добавленых в момент исключения.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/exceptions
 */
class ExceptionArgs extends Exception
{

    ################################### Properties ###################################

    /**
     * Аргументы объекта исключения.
     *
     * @var    array
     */
    private array $_args;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @author     Andrey J. Nazarov <mondegor@gmail.com>
     * @param      string  $message
     * @param      array  $args [string => mixed, ...]
     * @param      int  $code OPTIONAL
     * @param      Exception|null  $previous OPTIONAL
     */
    /*__override__*/ public function __construct(string $message, array $args, int $code = 0, $previous = null)
    {
        $this->_args = &$args;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Получение аргументов исключения.
     *
     * @author     Andrey J. Nazarov <mondegor@gmail.com>
     * @return     array  $data [string => mixed, ...]
     */
    public function getArgs(): array
    {
        return $this->_args;
    }

    /**
     * Получение аргумента по указанному ключу.
     *
     * @author     Andrey J. Nazarov <mondegor@gmail.com>
     * @param      string  $key
     * @return     mixed
     */
    public function get(string $key)
    {
        return $this->_args[$key] ?? null;
    }

}