<?php declare(strict_types=1);
namespace mrcore\storage\exceptions;
use Exception;
use RuntimeException;

/**
 * Exception: сообщение о недопустимом или ошибочном SQL запросе.
 *
 * @author  Andrey J. Nazarov
 */
class InvalidSqlQueryException extends RuntimeException
{

    public function __construct(string $message, int $code = 0, Exception $previous = null)
    {
        $errorMessage = 'Bad SQL Query';

        if (null !== $previous)
        {
            $errorMessage = $previous->getMessage();
        }

        parent::__construct($errorMessage . ' #extended-info# ' . $message, $code, $previous);
    }

}