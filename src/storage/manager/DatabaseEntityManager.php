<?php declare(strict_types=1);
namespace mrcore\storage\manager;
use mrcore\base\EventLogInterface;
use mrcore\debug\Assert;
use mrcore\storage\db\AbstractDatabase;
use mrcore\storage\exceptions\TransactionNotStarted;
use mrcore\storage\repository\AbstractDatabaseRepository;
use mrcore\storage\repository\DatabaseEntityRepository;
use mrcore\storage\repository\DatabaseRepository;
use mrcore\storage\TransactionInterface;
use mrcore\storage\TransactionManagerInterface;

// :TODO: EventLogInterface заменить на LoggerInterface

/**
 * Абстракция менеджера управления сущностями и
 * их репозиториями используя конкретную базу данных.
 *
 * @author  Andrey J. Nazarov
 */
class DatabaseEntityManager extends AbstractEntityManager implements TransactionManagerInterface
{

    public function __construct(private AbstractDatabase $connection,
                                protected ?EventLogInterface $logger = null) { }

    /**
     * @inheritdoc
     */
    public function getConnection(): AbstractDatabase
    {
        if (!$this->connection->isConnection())
        {
            $this->connection->open();
        }

        return $this->connection;
    }

    /**
     * @inheritdoc
     */
    public function startTransaction(): TransactionInterface
    {
        $connection = $this->getConnection();

        if ($connection->beginTransaction())
        {
            return $connection;
        }

        throw new TransactionNotStarted(sprintf('Transaction is not started on the provider %s', $connection->getProvider()));
    }

    /**
     * @inheritdoc
     */
    public function getRepository(string $repositoryClass): AbstractDatabaseRepository
    {
        return new $repositoryClass($this->connection, $this->logger);
    }

    /**
     * @inheritdoc
     */
    public function getEntityRepository(string $metaClass): DatabaseEntityRepository
    {
        $repositoryClass = DatabaseEntityRepository::class;

        if ($metaClass)
        {
            $repositoryClass
        }

        return new $repositoryClass
        (
            $this->getMappingEntity(),
            $metaClass
        );
    }

    /**
     * @inheritdoc
     */
    public function getMappingEntity(): MappingEntityInterface
    {

    }

}