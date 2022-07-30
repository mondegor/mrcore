<?php declare(strict_types=1);
namespace mrcore\storage\exceptions;
use RuntimeException;

/**
 * Exception: сообщение о невозможности начала транзакции.
 *
 * @author  Andrey J. Nazarov
 */
class TransactionNotStarted extends RuntimeException { }