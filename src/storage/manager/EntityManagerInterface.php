<?php declare(strict_types=1);
namespace mrcore\storage\manager;
use mrcore\storage\mapping\MappingEntityInterface;
use mrcore\storage\repository\EntityRepositoryInterface;
use mrcore\storage\repository\RepositoryInterface;
use mrcore\storage\StorageInterface;

/**
 * Интерфейс для реализации менеджера управления сущностями и
 * их репозиториями используя конкретное хранилище данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template T_Repository
 */
interface EntityManagerInterface extends StorageInterface
{
    /**
     * Возвращается репозиторий для работы напрямую с хранилищем данных.
     *
     * @param  class-string<T_Repository> $repositoryClass
     * @return  T_Repository
     */
    public function getRepository(string $repositoryClass): RepositoryInterface;

    /**
     * Возвращается репозиторий для работы с указанным типом сущности.
     */
    public function getEntityRepository(string $metaClass): EntityRepositoryInterface;

    /**
     * ORM объект для отображения сущности в хранилище данных.
     */
    public function getMappingEntity(): MappingEntityInterface;

}