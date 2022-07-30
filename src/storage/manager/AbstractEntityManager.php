<?php declare(strict_types=1);
namespace mrcore\storage\manager;
use mrcore\base\EventLogInterface;
use mrcore\storage\repository\EntityRepositoryInterface;

/**
 * Абстракция менеджера управления сущностями и
 * их репозиториями используя конкретное хранилище данных.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractEntityManager implements EntityManagerInterface
{
    /**
     * Инструмент фиксирования событий.
     */
    /*__abstract__*/ protected ?EventLogInterface $logger = null;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function getLogger(): EventLogInterface|null
    {
        return $this->logger;
    }

//    /**
//     * @inheritdoc
//     */
//    public function getRepository(string $repositoryClass): RepositoryInterface;
//
//    /**
//     * @inheritdoc
//     */
//    public function getEntityRepository(string $metaClass): EntityRepositoryInterface;
//
//    /**
//     * @inheritdoc
//     */
//    public function getMappingEntity(): MappingEntity
//    {
//
//    }
//
//    /**
//
//     */
//    public function getMappingEntity(): MappingEntityInterface
//    {
//        return new MappingEntity($this->connention, $this->logger);
//    }

    /**
     * @inheritdoc
     */
    public function getEntityRepository(string $metaClass): EntityRepositoryInterface
    {
        $metaClass


        $this->metaClass





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

}