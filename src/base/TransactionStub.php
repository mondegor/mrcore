<?php declare(strict_types=1);
namespace mrcore\base;

/**
 * Заглушка для имитации транзакции.
 *
 * @author  Andrey J. Nazarov
 */
class TransactionStub implements TransactionInterface
{
    /**
     * @inheritdoc
     */
    public function beginTransaction(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function commit(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function rollBack(): bool
    {
        return true;
    }

}