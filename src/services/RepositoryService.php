<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\base\EventLogInterface;
use mrcore\base\TraitSingleton;
use mrcore\storage\entity\EntityRepositoryInterface;
use mrcore\storage\entity\EntityManagerInterface;
use mrcore\storage\entity\StorageManager;

/**
 * Класс описывает сущность "Диспетчер соединений с различными ресурсами"
 * (с хранилищами данных, внешними API и другими сущностями").
 *
 * @author  Andrey J. Nazarov
 */
class RepositoryService implements ServiceInterface
{
    use TraitSingleton;

    /**
     * Стандартное название класса для конфигурации репозиториев моделей объектов.
     */
    public const REPOSITORIES_CONFIGURATOR_NAME = 'RepositoriesConfiguration';

    ################################### Properties ###################################

    /**
     * Массив менеджеров соединений.
     *
     * @var    array [string => EntityManagerInterface]
     */
    private array $managers = [];

    /**
     * Массив репозиториев моделей объектов.
     *
     * @var    array [string => EntityRepositoryInterface]
     */
    private array $repositories = [];

    #################################### Methods #####################################

    public function __construct(private ServiceGetterInterface $serviceBag,
                                private ConnService $connService,
                                private ?EventLogInterface $eventLog = null)
    {
        $this->_initSingleton();
    }

    /**
     * Возвращается ссылка на указанное соединение указанного типа
     * без гарантии того, что оно было открыто.
     */
    public function getRepository(string $metaClass, string $configClass = null): EntityRepositoryInterface
    {
        if (null === $configClass)
        {
            $configClass = substr($metaClass, 0, (int)strrpos($metaClass, '\\')) . '\\' . self::REPOSITORIES_CONFIGURATOR_NAME;
        }

        if (!isset($this->repositories[$configClass]))
        {
            if (!is_subclass_of($configClass, ServicesConfiguratorInterface::class))
            {
                trigger_error(sprintf('Configurator class %s is not found for entity meta %s', $configClass, $metaClass), E_USER_ERROR);
            }

            foreach ($configClass::getSpecification($this->serviceBag) as $key => $item)
            {
                $this->repositories[$configClass][$key]['params'] = $item;
            }
        }

        ##################################################################################

        $repositories = &$this->repositories[$configClass];

        if (!isset($repositories[$metaClass]['object']))
        {
            if (!isset($repositories[$metaClass]))
            {
                trigger_error(sprintf('No repository is linked to entity meta %s for configurator class %s', $metaClass, ($configClass ?? 'NONE')), E_USER_ERROR);
            }

            $repositories[$metaClass]['object'] = $this->_createRepository
            (
                $metaClass,
                ...$repositories[$metaClass]['params']
            );
        }

        return $repositories[$metaClass]['object'];
    }

    /**
     * Возвращается ссылкаа на указанное соединение указанного типа
     * без гарантии того, что оно было открыто.
     */
    protected function _getManager(string $connType, string $connName = null): EntityManagerInterface
    {
        $key = sprintf('%s|%s', $connType, $connName);  spl_object_id($entityManager)

        if (!isset($this->managers[$key]))
        {
            $this->managers[$key] = new StorageManager
            (
                new LazyConnection($this->connService, $connType, $connName),
                $this->eventLog // :TODO: если понадобится избавиться от прямой зависимости,
                                // :TODO: то в getSpecification нужно будет добавить доп. параметр
            );
        }

        return $this->managers[$key];
    }

    /**
     * @param  string|array  $class [string, string]
     * @param  array  $connSettings
     */
    protected function _createRepository(string $metaClass, string|array $class, array $connSettings): EntityRepositoryInterface
    {
        $entityClass = null;

        if (is_array($class))
        {
            [$class, $entityClass] = $class;
        }

        $manager = $this->_getManager(...$connSettings);
        $class = str_replace('{provider}', $manager->getLazyConnection()->getProvider(), $class);

        return new $class
        (
            $metaClass,
            $manager,
            $entityClass
        );
    }

}