<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method\mysql;
use mrcore\storage\mapping\method\DatabaseMethodLoad;

/**
 * Загрузка указанной сущности из базы данных MYSQL.
 *
 * @author  Andrey J. Nazarov
 */
class MethodLoad extends DatabaseMethodLoad
{
    /**
     * @inheritdoc
     */
    protected function _fetchRow(string $tableName,
                                 string $primaryName,
                                 array $selectNames,
                                 int|string $objectId,
                                 array $statusFields = null): ?array
    {
        $db = $this->_db();
        $where = '';

        if (null !== $statusFields)
        {
            $where = sprintf
            (
                " AND %s <> '%s'",
                $statusFields['statusField'],
                $db->escape($statusFields['statusRemove'])
            );
        }

        return $db->fetchRow
        (
            sprintf
            (
                "SELECT %s
                 FROM %s
                 WHERE %s = ?%s
                 LIMIT 1",
                implode(', ', $selectNames),$tableName, $primaryName, $where
            ),
            [$objectId]
        );
    }

}