<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method\mysql;
use mrcore\storage\mapping\method\DatabaseMethodCreate;

/**
 * Создание указанной сущности в базе данных MYSQL.
 *
 * @author  Andrey J. Nazarov
 */
class MethodCreate extends DatabaseMethodCreate
{
    /**
     * @inheritdoc
     */
    protected function _insert(string $tableName, array $fields, array $values): bool
    {
        $db = $this->_db();

        $result = $db->execQuery
        (
            sprintf
            (
                "INSERT INTO `%s` (`%s`) VALUES (%s)",
                $tableName,
                implode('`, `', $fields),
                implode(', ', array_fill(0, count($fields), '?'))
            ),
            $values
        );

        return $result && $db->getAffectedRows() > 0;
    }

    /**
     * @inheritdoc
     */
    protected function _getLastInsertedId(): int
    {
        return $this->_db()->getLastInsertedId();
    }

}