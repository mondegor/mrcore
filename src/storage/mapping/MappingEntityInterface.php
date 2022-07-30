<?php declare(strict_types=1);
namespace mrcore\storage\mapping;
use mrcore\storage\entity\EntityInterface;
use mrcore\storage\StorageInterface;

/**
 * Интерфейс для реализации обработчика вызова ORM методов.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES
 */
interface MappingEntityInterface extends StorageInterface
{
    /**
     * Исполнение указанного ORM метода взаимодействия сущности с хранилищем данных.
     *
     * @param  T_PROPERTIES|null $params
     */
    public function execMethod(string $methodClass, EntityInterface $entity, array $params = null): bool;

}