<?php declare(strict_types=1);
namespace mrcore\exceptions;

require_once 'mrcore/exceptions/ExceptionArgs.php';

/**
 * Exception: контейнер для передачи аргументов добавленых в момент исключения.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/exceptions
 */
class UnitTestException extends ExceptionArgs
{

}