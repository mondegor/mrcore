<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method;
use mrcore\storage\entity\EntityInterface;

/**
 * Интерфейс для реализации ORM метода взаимодействия сущности с хранилищем данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template   T_PROPERTIES
 */
interface StorageMethodInterface
{
    /**
     * Исполнение ORM метода.
     *
     * @param  T_PROPERTIES|null $params
     */
    public function execute(EntityInterface $entity, array $params = null): bool;

}