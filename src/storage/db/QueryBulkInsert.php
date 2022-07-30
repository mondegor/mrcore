<?php declare(strict_types=1);
namespace mrcore\storage\db;
use mrcore\storage\exceptions\DatabaseException;

// :TODO: объединить с Features, сделать абстрактный класс и наследовать от него MYSQL версию

/**
 * Класс для вставки пакетами записей в указанную таблицу (СУБД).
 *
 * @author  Andrey J. Nazarov
 */
class QueryBulkInsert
{
    /**
     * Максимальный размер разрешенного пакета.
     */
    public const MAX_ALLOWED_PACKET = 8388608;

    /**
     * Типы вставок.
     */
    public const TYPE_INSERT = 0,
                 TYPE_REPLACE = 1;

    ################################### Properties ###################################

    /**
     * Соединиение с БД.
     */
    private AbstractDatabase $_db;

    /**
     * Переменная максимально возможного пакета для вставки:
     * является минимальным из MAX_ALLOWED_PACKET и установленной переменной с СУБД.
     */
    private int $_maxAllowedPacket;

    /**
     * Шапка SQL запроса.
     */
    private string $_sql;

    /**
     * Размер шапки SQL запроса.
     */
    private int $_sqlSize;

    /**
     * Список добавленных записей для вставки в таблицу:
     * хранится в виде текстовой строки.
     */
    private string $_rows = '';

    /**
     * Размер списка добавленных записей.
     */
    private int $_rowsSize = 0;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      AbstractDatabase  $db
     * @param      int  $type [TYPE_INSERT, TYPE_REPLACE]
     * @param      string  $table
     * @param      array  $fields
     * @throws     DatabaseException
     */
    public function __construct(AbstractDatabase $db, int $type, string $table, array $fields)
    {
        $this->_db = &$db;

        if ('' === $table)
        {
            throw new DatabaseException(sprintf('Field "$table" is not set'));
        }

        $this->_sql = (self::TYPE_REPLACE === $type ? 'REPLACE' : 'INSERT') . ' ' . $table . ' (' . implode(',', $fields) . ') VALUES ';
        $this->_sqlSize = strlen($this->_sql);

        $row = $db->fetchRow("SHOW VARIABLES WHERE variable_name = 'max_allowed_packet'");

        // максимальный пакет формируется не более MAX_ALLOWED_PACKET
        $this->_maxAllowedPacket = min((int)$row['Value'], self::MAX_ALLOWED_PACKET) - 512;
    }

    /**
     * Признак, была ли добавлена хотя бы одна запись.
     */
    public function isAdded(): bool
    {
        return $this->_rowsSize > 0;
    }

    /**
     * Добавление указанной записи.
     * Если размер пакета превысит максимальный,
     * то все накопленные записи будут добавлены в таблицу БД.
     *
     * @param      mixed  $row (string - field1,field2,field3,..) or string[]
     */
    public function add($row): void
    {
        assert(is_string($row) || is_array($row));

        $row = ",\n" . '(' . (is_array($row) ? implode(',', $row) : $row) . ')';
        $size = mb_strlen($row);

        // чтобы не допустить переполнение буфера СУБД, данные сохраняются досрочно
        if (($this->_sqlSize + $this->_rowsSize + $size) > $this->_maxAllowedPacket)
        {
            $this->flush();

            $this->_rows = $row;
            $this->_rowsSize = $size;
        }
        else
        {
            $this->_rows .= $row;
            $this->_rowsSize += $size;
        }
    }

    /**
     * Сброс текущего пакета в таблицу БД.
     */
    public function flush(): void
    {
        if ($this->_sqlSize > 0 && $this->_rowsSize > 0)
        {
            $this->_db->execQuery($this->_sql . substr($this->_rows, 1));

            $this->_rows = '';
            $this->_rowsSize = 0;
        }
    }

}