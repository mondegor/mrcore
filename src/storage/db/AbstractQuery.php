<?php declare(strict_types=1);
namespace mrcore\storage\db;

/**
 * Абстракция формирующая доступ к результату запроса базы данных.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractQuery
{

    public function __construct(protected AbstractDatabase $connDb, protected $resource) { }

    /**
     * Fetch the current row of query.
     *
     * @return  array [string => mixed, ...] or (if $assoc = false) [int => mixed, ...]
     */
    abstract public function fetch(bool $assoc = true): array;

    /**
     * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query.
     */
    abstract public function getNumRows(): int;

    public function getLastInsertedId(): int
    {
        return $this->connDb->getLastInsertedId();
    }

    public function getAffectedRows(): int
    {
        return $this->connDb->getAffectedRows();
    }

    /**
     * Освобождение памяти от query.
     */
    abstract public function freeResult(): void;

}