<?php declare(strict_types=1);
namespace mrcore\storage\db\mysql;
use mrcore\storage\db\AbstractQuery;

/**
 * Класс формирующий доступ к результату запроса MYSQL.
 *
 * @author  Andrey J. Nazarov
 */
class MysqlQuery extends AbstractQuery
{
    /**
     * @inheritdoc
     */
    public function fetch(bool $assoc = true): array
    {
        if ($assoc)
        {
            return mysqli_fetch_assoc($this->resource);
        }

        return mysqli_fetch_row($this->resource);
    }

    /**
     * @inheritdoc
     */
    public function getNumRows(): int
    {
        return mysqli_num_rows($this->resource);
    }

    /**
     * @inheritdoc
     */
    public function freeResult(): void
    {
        mysqli_free_result($this->resource);
    }

}