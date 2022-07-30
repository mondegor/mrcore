<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method\mysql;
use mrcore\storage\mapping\method\DatabaseMethodRemove;

/**
 * Удаление указанной сущности из базы данных MYSQL.
 *
 * @author  Andrey J. Nazarov
 */
class MethodRemove extends DatabaseMethodRemove
{
    /**
     * @inheritdoc
     */
    protected function _remove(string $tableName,
                               string $primaryName,
                               int|string $objectId): bool
    {
        return $this->_db()->execQuery
        (
            sprintf
            (
                "DELETE FROM %s
                 WHERE %s = ?",
                $tableName, $primaryName
            ),
            [$objectId]
        );
    }

    /**
     * @inheritdoc
     */
    protected function _markAsRemoved(string $tableName,
                                      string $primaryName,
                                      string $statusName,
                                      string $datetimeStatusName,
                                      int|string $objectId,
                                      string $statusValue,
                                      string $datetimeStatusValue): bool
    {
        return $this->_db()->execQuery
        (
            sprintf
            (
                "UPDATE %s
                 SET %s = ?,
                     %s = ?
                 WHERE %s = ?",
                $tableName, $statusName, $datetimeStatusName, $primaryName
            ),
            [$statusValue, $datetimeStatusValue, $objectId]
        );
    }

}