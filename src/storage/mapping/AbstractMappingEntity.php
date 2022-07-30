<?php declare(strict_types=1);
namespace mrcore\storage\mapping;
use mrcore\base\EventLogInterface;
use mrcore\storage\entity\EntityInterface;
use mrcore\storage\mapping\method\StorageMethodInterface;

/**
 * Абстракция обработчика вызова ORM методов.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_StorageMethod
 */
abstract class AbstractMappingEntity implements MappingEntityInterface
{
    /**
     * Инструмент фиксирования событий.
     */
    /*__abstract__*/ protected ?EventLogInterface $logger = null;

    /**
     * Список объектов ORM методов взаимодействия сущности с хранилищем данных.
     *
     * @var  array<string, StorageMethodInterface> // key - StorageMethodInterface::class,
     */
    private array $storageMethods = [];

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function getLogger(): EventLogInterface|null
    {
        return $this->logger;
    }

    /**
     * Если $methodClass содержит переменную {provider}, то она будет заменена
     * на название провайдера текущего хранилища данных.
     *
     * @inheritdoc
     */
    public function execMethod(string $methodClass, EntityInterface $entity, array $params = null): bool
    {
        return $this->_getMethod($methodClass)->execute($entity, $params);
    }

    ##################################################################################

    /**
     * Возвращается провайдер взаимодействия хранилища данных.
     */
    abstract protected function _connectionProvider(): string;

    /**
     * Создание ORM метода взаимодействия сущности с хранилищем данных.
     *
     * @param  class-string<T_StorageMethod> $class
     * @return  T_StorageMethod
     */
    abstract protected function _createMethod(string $class): StorageMethodInterface;

    /**
     * Возвращается объект указанного ORM метода.
     */
    protected function _getMethod(string $class): StorageMethodInterface
    {
        $class = str_replace('{provider}', $this->_connectionProvider(), $class);

        if (!isset($this->storageMethods[$class]))
        {
            $this->storageMethods[$class] = $this->_createMethod($class);
        }

        return $this->storageMethods[$class];
    }

}