<?php declare(strict_types=1);
namespace mrcore\storage;

/**
 * Интерфейс механизма транзакций.
 *
 * @author  Andrey J. Nazarov
 */
interface TransactionInterface
{
    /**
     * Открытие транзакции.
     *
     * ISOLATION TYPES:
     *   READ UNCOMMITTED - все данные доступны во всех транзакциях;
     *   READ COMMITTED - данные транзакции после её коммита доступны в другой незавершенной транзакции;
     *   REPEATABLE READ - данные транзакции доступны только в новых открытых транзакциях;
     *   SERIALIZABLE - все транзакции следуют строго друг за другом;
     */
    public function beginTransaction(): bool;

    /**
     * Подтверждение транзакции.
     */
    public function commit(): bool;

    /**
     * Откат транзакции.
     */
    public function rollBack(): bool;

}