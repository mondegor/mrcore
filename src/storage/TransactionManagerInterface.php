<?php declare(strict_types=1);
namespace mrcore\storage;
use mrcore\storage\exceptions\TransactionNotStarted;

/**
 * Интерфейс для реализации менеджера транзакций.
 *
 * @author  Andrey J. Nazarov
 */
interface TransactionManagerInterface
{
    /**
     * Открытие транзакции вместе с возвращением объекта транзакции.
     *
     * @throws TransactionNotStarted
     */
    public function startTransaction(): TransactionInterface;

}