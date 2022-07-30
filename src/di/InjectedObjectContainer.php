<?php declare(strict_types=1);
namespace mrcore\di;
use mrcore\debug\Assert;
use mrcore\di\exceptions\ContainerEntryException;
use mrcore\di\exceptions\ContainerException;
use Psr\Container\ContainerInterface;

/**
 * It is container for injected objects.
 *
 * @author  Andrey J. Nazarov
 */
class InjectedObjectContainer implements ContainerInterface
{
    /**
     * @param  array<string, string> $packages // список пакетов, к котором привязаны классы наследуемые от DependencyConfigInterface
     *                                         // key - package, value - className
     * @param  array<string, callable|object> $objects // список зарегистрированных внедряемых объектов
     */
    public function __construct(private array $packages, private array $objects) { }

    /**
     * Регистрация нового класса внедряемого объекта в контейнере.
     */
    public function registerClass(string $class, string|callable|object $factoryOrObject): static
    {
        assert(!isset($this->objects[$class]));

        $this->objects[$class] = $factoryOrObject;

        return $this;
    }

    /**
     * Зарегистрирован ли уже указанный класс в контейнере?
     * В качестве $class может выступать имя класса или его псевдоним.
     */
    public function has(string $class): bool
    {
        if (!isset($this->objects[$class]))
        {
            $this->_autoloadConfig($class);
        }

        return isset($this->objects[$class]);
    }

    /**
     * Возвращается внедряемый объект указанного класса.
     * В качестве $class может выступать имя класса или его псевдоним.
     * Предварительно название класса или пакет связанный с ним
     * должен быть зарегистрирован в контейнере.
     *
     * @throws ContainerEntryException
     * @throws ContainerException
     */
    public function get(string $class): object
    {
        if (!isset($this->objects[$class]))
        {
            if (!$this->_autoloadConfig($class))
            {
                ContainerException::configNotFound($class, __CLASS__);
            }

            if (!isset($this->objects[$class]))
            {
                throw ContainerEntryException::entryNotFound($class, __CLASS__);
            }
        }

        if (is_array($this->objects[$class]))
        {
            [$factoryClass, $factoryMethod] = $this->objects[$class];
            $this->objects[$class] = $factoryClass::$factoryMethod($class, $this);
        }
        else if (is_string($this->objects[$class]))
        {
            assert(Assert::instanceOf($this->objects[$class], ObjectFactoryInterface::class), Assert::instanceOfMessage($this->objects[$class], ObjectFactoryInterface::class));
            $this->objects[$class] = $this->objects[$class]::createObject($class, $this);
        }

        return $this->objects[$class];
    }

    /**
     * Вызывается в конце работы приложения, для завершения работы всех
     * созданных внедряемых объектов поддерживающих интерфейс ObjectFinaliseInterface.
     */
    public function finalise(): void
    {
        foreach ($this->objects as $object)
        {
            if ($object instanceof ObjectFinaliseInterface)
            {
                $object->finalise();
            }
        }
    }

    /**
     * Поиск зарегистрированного пакета в который входит указанный класс, если он будет найден,
     * то загрузится привязанная к нему конфигурация создания внедряемых объектов из этого пакета.
     */
    protected function _autoloadConfig(string $class): bool
    {
        foreach ($this->packages as $package => $configClass)
        {
            if (0 === strncmp($package, $class, strlen($package)))
            {
                assert(Assert::instanceOf($configClass, DependencyConfigInterface::class), Assert::instanceOfMessage($configClass, DependencyConfigInterface::class));

                foreach ($configClass::getClassToFactory() as $objectClass => $objectFactory)
                {
                    // если класс уже зарегистрирован ранее, то текущая конфигурация его игнорирует
                    if (!isset($this->objects[$objectClass]))
                    {
                        $this->objects[$objectClass] = $objectFactory;
                    }
                }

                return true;
            }
        }

        return false;
    }

}