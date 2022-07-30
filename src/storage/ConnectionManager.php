<?php declare(strict_types=1);
namespace mrcore\storage;
use mrcore\base\Environment;
use mrcore\debug\Assert;
use mrcore\di\ConnectionFactoryInterface;
use mrcore\di\exceptions\ContainerEntryException;
use Psr\Container\ContainerInterface;

/**
 * Менеджер соединений с различными ресурсами, такими как:
 * хранилища данных, внешние API и другие подобные соединения наследуемые от ConnectionInterface.
 *
 * Название соединения должно быть в формате {type}:{name},
 *   где {type} - тип соединения, {name} - название соединения
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES
 * @template  T_CONNECTION_CONFIG=array{class: string, // класс соединения наследуемый от ConnectionInterface
 *                                      ?factoryClass: string, // класс-фабрика наследуемый от ConnectionFactoryInterface для создания объекта соединения
 *                                      ?environmentCallable: callable, // подгрузка дополнительных параметров переданных ОС
 *                                                                      // function (Environment, &T_PROPERTIES)
 *                                      params: T_PROPERTIES} // параметры соединения
 * @template  T_Connection
 */
class ConnectionManager implements ContainerInterface
{
    /**
     * Массив зарегистрированных соединений.
     *
     * @var  array<string, T_CONNECTION_CONFIG|ConnectionInterface>
     */
    private array $connections;

    #################################### Methods #####################################

    /**
     * @param  array<string, T_CONNECTION_CONFIG>  $config
     */
    public function __construct(array $config, private Environment $environment)
    {
        $this->connections = $config;
    }

    /**
     * Зарегистрировано ли указанное соединение?
     */
    public function has(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * Возвращается провайдер указанного соединения.
     *
     * @throws ContainerEntryException
     */
    public function getProvider(string $name): string
    {
        if (!isset($this->connections[$name]))
        {
            // sprintf('Connection %s is not found in container %s', $name, __CLASS__)
            throw ContainerEntryException::entryNotFound($name, __CLASS__);
        }

        if (is_object($this->connections[$name]))
        {
            return $this->connections[$name]->getProvider();
        }

        assert(isset($this->connections[$name]['params']['provider']));

        return $this->connections[$name]['params']['provider'];
    }

    /**
     * Возвращается объект указанного соединения.
     * :WARNING: При создании объекта соединение не открывается.
     *
     * @throws ContainerEntryException
     */
    public function get(string $name): ConnectionInterface
    {
        if (!isset($this->connections[$name]))
        {
            // sprintf('Connection %s is not found in container %s', $name, __CLASS__)
            throw ContainerEntryException::entryNotFound($name, __CLASS__);
        }

        if (!is_object($this->connections[$name]))
        {
            assert(isset($this->connections[$name]['class']));
            assert(isset($this->connections[$name]['params']));

            if (isset($this->connections[$name]['environmentCallable']))
            {
                assert(is_callable($this->connections[$name]['environmentCallable']));

                [$class, $method] = $this->connections[$name]['environmentCallable'];

                $class::$method
                (
                    $this->environment,
                    $this->connections[$name]['params']
                );
            }

            $this->connections[$name] = $this->_createConnection
            (
                $name,
                $this->connections[$name]['class'],
                $this->connections[$name]['factoryClass'],
                $this->connections[$name]['params']
            );
        }

        return $this->connections[$name];
    }

    /**
     * Закрытие указанного соединения, если оно было открыто.
     */
    public function close(string $name): void
    {
        if (!isset($this->connections[$name]))
        {
            // sprintf('Connection %s is not found in container %s', $name, __CLASS__)
            throw ContainerEntryException::entryNotFound($name, __CLASS__);
        }

        if (is_object($this->connections[$name]))
        {
            $this->connections[$name]->close();
        }
    }

    /**
     * Возвращаются все созданные соединения указанного типа.
     *
     * @return  array<string, ConnectionInterface> // key - название соединения
     */
    public function all(string $type): array
    {
        $result = [];

        foreach ($this->connections as $name => $object)
        {
            if (is_object($object) && 0 === strncmp($type . ':', $name, strlen($type) + 1))
            {
                $result[$name] = $object;
            }
        }

        return $result;
    }

    /**
     * Создание объекта соединения на основе указанного класса и его параметров.
     *
     * @param   class-string<T_Connection> $class
     * @param   T_PROPERTIES $params
     * @return  T_Connection
     */
    protected function _createConnection(string $name, string $class, string $factoryClass, array $params): ConnectionInterface
    {
        assert(Assert::instanceOf($class, ConnectionInterface::class), Assert::instanceOfMessage($class, ConnectionInterface::class));
        assert(Assert::instanceOf($factoryClass, ConnectionFactoryInterface::class), Assert::instanceOfMessage($factoryClass, ConnectionFactoryInterface::class));

        return $factoryClass::createConnection($class, $name, $params);
    }

}