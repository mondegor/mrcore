<?php declare(strict_types=1);
namespace mrcore\storage\repository;
use mrcore\base\EventLogInterface;
use mrcore\storage\db\AbstractDatabase;

/**
 * Абстракция репозитория для работы напрямую с хранилищем данных.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractDatabaseRepository implements RepositoryInterface
{

    public function __construct(private AbstractDatabase $db,
                                private ?EventLogInterface $logger = null) { }

    ##################################################################################

    /**
     * Возвращается ОТКРЫТОЕ соединение с базой данных.
     */
    protected function _db(): AbstractDatabase
    {
        if (!$this->db->isConnection())
        {
            $this->db->open();
        }

        return $this->db;
    }

    /**
     * @see EventLogInterface::add().
     */
    protected function _event(int $eventType, array|string $data): void
    {
        if (null !== $this->logger)
        {
            $this->logger->add($eventType, $data);
        }
    }

}