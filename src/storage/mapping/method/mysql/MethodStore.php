<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method\mysql;
use mrcore\storage\mapping\method\DatabaseMethodStore;

/**
 * Сохранение указанной сущности в базе данных MYSQL.
 *
 * @author  Andrey J. Nazarov
 */
class MethodStore extends DatabaseMethodStore
{
    /**
     * @inheritdoc
     */
    protected function _update(string $tableName,
                               string $primaryName,
                               int|string $objectId,
                               array $fields,
                               array $values,
                               array $statusFields = null): bool
    {
        $db = $this->_db();
        $set = '';
        $where = '';

        foreach ($fields as $field)
        {
            $set .= sprintf(', %s = ?', $field);
        }

        $values[] = $objectId;

        if (null !== $statusFields)
        {
            $where = sprintf
            (
                " AND %s <> '%s'",
                $statusFields['statusField'],
                $db->escape($statusFields['statusRemove'])
            );
        }

        ##################################################################################

        return $db->execQuery
        (
            sprintf
            (
                "UPDATE %s
                 SET %s
                 WHERE %s = ?%s",
                $tableName, substr($set, 2), $primaryName, $where
            ),
            $values
        );
    }

}