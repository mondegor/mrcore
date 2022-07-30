<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method;
use mrcore\base\EventLogInterface;
use mrcore\storage\db\AbstractDatabase;

// :TODO: EventLogInterface заменить на LoggerInterface

/**
 * Абстракция ORM метода взаимодействия сущности с базой данных.
 *
 * @author  Andrey J. Nazarov
 */
abstract class DatabaseMethod extends AbstractMethod
{

    public function __construct(private AbstractDatabase $db,
                                protected ?EventLogInterface $logger = null) { }

    ##################################################################################

    /**
     * Возвращается ОТКРЫТОЕ соединение с хранилищем данных.
     */
    protected function _db(): AbstractDatabase
    {
        if (!$this->db->isConnection())
        {
            $this->db->open();
        }

        return $this->db;
    }

}