<?php declare(strict_types=1);
namespace mrcore\storage\db;

/**
 * Абстракция расширяющая возможности адаптера соединения.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractFeatures
{

    public function __construct(protected AbstractDatabase $connDb) { }

    /**
     * Создание копии структуры указанной таблицы.
     */
    abstract public function createCopyTableStructure(string $tableName, string $newTableName, int $options = null): bool;

    /**
     * Возвращается структура таблицы.
     *
     * @return  array|null [primaryKey => array, fields => [string => [type => string,
     *                                                                 dbtype => string,
     *                                                                 length => string,
     *                                                                 isNull => bool,
     *                                                                 values => [],
     *                                                                 default => string,
     *                                                                 isPrimary => bool,
     *                                                                 isAutoInc => bool]]
     */
    abstract public function getTableStructure(string $tableName): ?array;

}