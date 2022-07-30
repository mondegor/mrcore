<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\storage\entity\EntityRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * It is container for shared services.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractServiceBag implements ServiceGetterInterface, ContainerInterface
{
    /**
     * Стандартное название класса для конфигурации встраиваемых сервисов.
     */
    public const SERVICES_CONFIGURATOR_NAME = 'ServicesConfigurator';

    ################################### Properties ###################################

    /**
     * Массив зарегистрированных встраиваемых сервисов.
     *
     * @var    array [string => [init => [object, string] OPTIONAL,
     *                           object => ServiceInterface] OPTIONAL, ...]
     */
    private array $services = [];

    #################################### Methods #####################################

    /**
     * Регистрация нового встраиваемого сервиса под указанным именем.
     *
     * @param  ServiceInterface|string|array $object [object, string] // [object|class, method]
     */
    public function registerService(string $name, ServiceInterface|string|array $object): AbstractServiceBag
    {
        // assert(!isset($this->services[$name]));

        $type = is_object($object) ? 'object' : 'init';
        $this->services[$name][$type] = $object;

        return $this;
    }

    /**
     * Регистрация нового встраиваемого сервиса под указанным именем.
     */
    public function registerServiceUsingConfig(string $class, string $configClass, ServiceInterface|string $object): AbstractServiceBag
    {
        assert(!empty($configClass));
        assert(!isset($this->services[$configClass][$class]));

        $this->services[$configClass][$class] = is_string($object) ?
            ['init' => [$configClass, $object]] :
            ['object' => $object];

        return $this;
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    public function get(string $id): mixed
    {
        return $this->getService($id);
    }

    /**
     * @inheritdoc
     */
    public function getService(string $name, string $configClass = null): object
    {
        if (str_contains($name, '.'))
        {
            assert(null === $configClass);
            $services = &$this->services;
        }
        else
        {
            if (null === $configClass)
            {
                $configClass = substr($name, 0, (int)strrpos($name, '\\')) . '\\' . self::SERVICES_CONFIGURATOR_NAME;
            }

            if (!isset($this->services[$configClass]))
            {
                if (!is_subclass_of($configClass, ServicesConfiguratorInterface::class))
                {
                    trigger_error(sprintf('Configurator class %s is not found for service %s', $configClass, $name), E_USER_ERROR);
                }

                foreach ($configClass::getSpecification($this) as $key => $object)
                {
                    $this->registerServiceUsingConfig($key, $configClass, $object);
                }
            }

            $services = &$this->services[$configClass];
        }

        ##################################################################################

        if (!isset($services[$name]['object']))
        {
            if (!isset($services[$name]))
            {
                trigger_error(sprintf('Service %s is not registered for config: %s', $name, ($configClass ?? 'NONE')), E_USER_ERROR);
            }

            $services[$name]['object'] = call_user_func($services[$name]['init'], $this);
        }

        return $services[$name]['object'];
    }

    /**
     * @inheritdoc
     */
    public function getRepository(string $entityClass, string $configClass = null): EntityRepositoryInterface
    {
        /* @var RepositoryService $repositoryService */
        $repositoryService = $this->getService('sys.repository');

        return $repositoryService->getRepository($entityClass, $configClass);
    }

    /**
     * Вызывается в конце работы приложения, для завершения работы встраиваимых сервисов.
     */
    public function finalise(): void
    {
        foreach ($this->services as $service)
        {
            if (isset($service['object']) &&
                ($service['object'] instanceof ServiceFinaliseInterface))
            {
                $service['object']->finalise();
            }
        }
    }

}