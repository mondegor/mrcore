<?php declare(strict_types=1);
namespace mrcore\storage;
use mrcore\base\EventLogInterface;

/**
 * Интерфейс для реализации доступа к хранилищу данных и
 * логирования операций обращения к нему.
 *
 * @author  Andrey J. Nazarov
 */
interface StorageInterface
{
    /**
     * Возвращается текущее соединение с хранилищем данных.
     */
    public function getConnection(): ConnectionInterface;

    /**
     * Интерфейс инструмента фиксирования событий.
     */
    public function getLogger(): EventLogInterface|null;

}