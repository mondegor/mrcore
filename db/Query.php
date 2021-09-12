<?php declare(strict_types=1);
namespace mrcore\db;

/**
 * Класс формирующий доступ к результату запроса БД.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/db
 */
class Query
{
    /**
     * Ссылка на текущее соединение с БД.
     *
     * @var    Adapter
     */
    protected Adapter $_conn;

    /**
     * Ссылка на текущий запрос к БД.
     *
     * @var    mixed
     */
    protected $_resource;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      Adapter  $conn
     * @param      resource  $resource
     */
    public function __construct(Adapter $conn, $resource)
    {
        $this->_conn = &$conn;
        $this->_resource = $resource;
    }

    /**
     * Fetch the current row of query.
     *
     * @param      bool  $assoc OPTIONAL
     * @return     array [string => mixed, ...] or (if $assoc = false) [int => mixed, ...]
     */
    public function fetch(bool $assoc = true): array
    {
        return $this->_conn->fetch($this->_resource, $assoc);
    }

    /**
     * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query.
     *
     * @return     int  The number of rows
     */
    public function getNumRows(): int
    {
        return $this->_conn->getNumRows($this->_resource);
    }

    /**
     * Returns the last inserted id by the previous INSERT query.
     *
     * @return     int  The last inserted id
     */
    public function getLastInsertedId(): int
    {
        return $this->_conn->getLastInsertedId();
    }

    /**
     * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query.
     *
     * @return     int  The number of rows
     */
    public function getAffectedRows(): int
    {
        return $this->_conn->getAffectedRows();
    }

    /**
     * Освобождение памяти от query.
     */
    public function freeResult(): void
    {
        $this->_conn->freeResult($this->_resource);
        $this->_resource = null;
    }

    /**
     * Деструктор класса.
     * Закрытие текущего результата запроса к БД.
     */
    public function __destruct()
    {// var_dump('call __destruct() of class: ' . get_class($this));
        $this->freeResult();
        unset($this->_conn);

        // /*__not_required__*/ parent::__destruct(); /*__WARNING_not_to_remove__*/
    }

}