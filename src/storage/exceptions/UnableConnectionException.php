<?php declare(strict_types=1);
namespace mrcore\exceptions;
use RuntimeException;

/**
 * Exception: сообщение о невозможности подключения к критичным ресурсам.
 *
 * @author  Andrey J. Nazarov
 */
class UnableConnectionException extends RuntimeException { }