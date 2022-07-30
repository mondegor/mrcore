<?php declare(strict_types=1);
namespace mrcore\storage\mapping;
use mrcore\base\EventLogInterface;
use mrcore\debug\Assert;
use mrcore\storage\db\AbstractDatabase;
use mrcore\storage\mapping\method\DatabaseMethod;
use mrcore\storage\mapping\method\StorageMethodInterface;

// :TODO: EventLogInterface заменить на LoggerInterface

/**
 * Обработчик вызова ORM методов взаимодействующих с базой данных.
 *
 * @author  Andrey J. Nazarov
 */
class DatabaseMappingEntity extends AbstractMappingEntity
{

    public function __construct(private AbstractDatabase $connection,
                                protected ?EventLogInterface $logger = null) { }

    /**
     * @inheritdoc
     */
    public function getConnection(): AbstractDatabase
    {
        return $this->connection;
    }

    ##################################################################################

    /**
     * @inheritdoc
     */
    protected function _connectionProvider(): string
    {
        return $this->connection->getProvider();
    }

    /**
     * @inheritdoc
     */
    protected function _createMethod(string $class): StorageMethodInterface
    {
        assert(Assert::instanceOf($class, DatabaseMethod::class), Assert::instanceOfMessage($class, DatabaseMethod::class));

        return new $class($this->connection, $this->logger);
    }

}