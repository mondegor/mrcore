<?php declare(strict_types=1);
namespace mrcore\storage\repository;
use mrcore\storage\entity\EntityInterface;
use mrcore\storage\entity\AbstractEntityMeta;
use mrcore\storage\mapping\MappingEntityInterface;

/**
 * Абстракция репозитория конкретной сущности отображаемой в хранилище данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES
 */
abstract class AbstractEntityRepository implements EntityRepositoryInterface
{
    /**
     * Префикс к имени базового класса ORM метода.
     */
    private const MAPPING_METHOD_CLASS = 'mrcore\storage\mapping\method\{provider}\Method';

    ################################### Properties ###################################

    /**
     * Метаданные сущности с которой связан репозиторий.
     */
    /*__abstract__*/ protected AbstractEntityMeta $entityMeta;

    /**
     * Обработчик вызова ORM методов.
     */
    /*__abstract__*/ protected MappingEntityInterface $mappingEntity;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function getMeta(): AbstractEntityMeta
    {
        return $this->entityMeta;
    }

    /**
     * @inheritdoc
     */
    public function getEntity(int|string $id, array $props = null): EntityInterface
    {
        return $this->createEntity($props)
                    ->setId($id);
    }

    /**
     * @inheritdoc
     */
    public function fetchEntity(int|string $id, array $names = null): EntityInterface|null
    {
        $entity = $this->createEntity()->setId($id);

        if (!$this->mappingEntity->execMethod(self::MAPPING_METHOD_CLASS . 'Load', $entity, ['names' => $names]))
        {
            return null;
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function create(array|EntityInterface $entity): bool
    {
        if (is_array($entity))
        {
            $entity = $this->createEntity($entity);
        }

        return $this->mappingEntity->execMethod(self::MAPPING_METHOD_CLASS . 'Create', $entity);
    }

    /**
     * @inheritdoc
     */
    public function store(array|EntityInterface $entity): bool
    {
        if (is_array($entity))
        {
            $entity = $this->createEntity($entity);
        }

        return $this->mappingEntity->execMethod(self::MAPPING_METHOD_CLASS . 'Store', $entity);
    }

    /**
     * @inheritdoc
     */
    public function remove(int|string|EntityInterface $idOrEntity, bool $markAsRemoved = false): bool
    {
        if (!is_object($idOrEntity))
        {
            $idOrEntity = $this->getEntity($idOrEntity);
        }

        return $this->mappingEntity->execMethod(self::MAPPING_METHOD_CLASS . 'Remove', $idOrEntity, ['markAsRemoved' => $markAsRemoved]);
    }

    /**
     * @inheritdoc
     */
    public function execMethod(string $methodClass, array|EntityInterface $entity, array $params = null): bool
    {
        if (is_array($entity))
        {
            $entity = $this->createEntity($entity);
        }

        return $this->mappingEntity->execMethod($methodClass, $entity, $params);
    }

}