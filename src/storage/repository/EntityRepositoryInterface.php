<?php declare(strict_types=1);
namespace mrcore\storage\repository;
use mrcore\storage\entity\AbstractEntityMeta;
use mrcore\storage\entity\EntityInterface;

/**
 * Интерфейс репозитория конкретной сущности отображаемой в хранилище данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES
 */
interface EntityRepositoryInterface
{
    /**
     * Возвращается метаданные сущности, привязанной к данному репозиторию.
     */
    public function getMeta(): AbstractEntityMeta;

    /**
     * Создаётся и возвращается новая сущность.
     *
     * @param  T_PROPERTIES  $props
     */
    public function createEntity(array $props = null): EntityInterface;

    /**
     * Возвращается сущность с установленным идентификатором (без загрузки из хранилища).
     *
     * @param  T_PROPERTIES  $props
     */
    public function getEntity(int|string $id, array $props = null): EntityInterface;

    /**
     * Возвращается сущность с данными загруженными из хранилища.
     *
     * @param  string[]|null $names
     */
    public function fetchEntity(int|string $id, array $names = null): EntityInterface|null;

    /**
     * Создание указанной сущности в хранилище данных.
     *
     * @param  T_PROPERTIES|EntityInterface $entity
     */
    public function create(array|EntityInterface $entity): bool;

    /**
     * Сохранение указанной сущности в хранилище данных.
     *
     * @param  T_PROPERTIES|EntityInterface $entity
     */
    public function store(array|EntityInterface $entity): bool;

    /**
     * Удаление указанной сущности из хранилища данных.
     */
    public function remove(int|string|EntityInterface $idOrEntity, bool $markAsRemoved = false): bool;

    /**
     * Исполнение указанного метода взаимодействия сущности с хранилищем данных.
     */
    public function execMethod(string $methodClass, array|EntityInterface $entity, array $params = null): bool;

}