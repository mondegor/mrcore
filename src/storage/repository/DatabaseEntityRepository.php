<?php declare(strict_types=1);
namespace mrcore\storage\repository;
use mrcore\base\EventLogInterface;
use mrcore\debug\Assert;
use mrcore\storage\db\AbstractDatabase;
use mrcore\storage\entity\AbstractEntityMeta;
use mrcore\storage\entity\Entity;
use mrcore\storage\entity\EntityInterface;
use mrcore\storage\mapping\DatabaseMappingEntity;

/**
 * Репозиторий конкретной сущности отображаемой в базу данных.
 *
 * @author  Andrey J. Nazarov
 */
class DatabaseEntityRepository extends AbstractEntityRepository
{
    /**
     * Класс сущности с которой связан репозиторий.
     */
    protected string $entityClass = Entity::class;

    #################################### Methods #####################################

    public function __construct(protected DatabaseMappingEntity $mappingEntity,
                                protected AbstractEntityMeta $metaClass,
                                string $entityClass = null)
    {
        if (null !== $entityClass)
        {
            $this->entityClass = $entityClass;
        }
    }

    /**
     * @inheritdoc
     */
    public function createEntity(array $props = null): EntityInterface
    {
        assert(Assert::instanceOf($this->entityClass, Entity::class), Assert::instanceOfMessage($this->entityClass, Entity::class));

        return new $this->entityClass($this->entityMeta, $props);
    }

    ##################################################################################

    /**
     * Возвращается ОТКРЫТОЕ соединение с базой данных.
     */
    protected function _db(): AbstractDatabase
    {
        $connection = $this->mappingEntity->getConnection();

        if (!$connection->isConnection())
        {
            $connection->open();
        }

        return $connection;
    }

    /**
     * @see EventLogInterface::add().
     */
    protected function _event(int $eventType, array|string $data): void
    {
        $logger = $this->mappingEntity->getLogger();

        if (null !== $logger)
        {
            $logger->add($eventType, $data);
        }
    }

}