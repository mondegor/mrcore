<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\base\Environment;
use mrcore\storage\entity\EntityRepositoryInterface;

/**
 * Интерфес получения сервисов и репозиториев.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_Service
 * @template  T_Repository
 */
interface ServiceGetterInterface
{
    /**
     * Возвращается объект доступа к внешнему окружению организованной ОС.
     */
    public function getEnv(): Environment;

    /**
     * Возвращается сервис по его имени или источнику.
     *
     * @param class-string<T_Service> $name
     * @return T_Service
     */
    public function getService(string $name, string $configClass = null);

    /**
     * Возвращается репозиторий по источнику метаданных модели объекта.
     *
     * @param class-string<T_Repository> $entityClass
     * @return T_Repository
     */
    public function getRepository(string $entityClass, string $configClass = null): EntityRepositoryInterface;

}