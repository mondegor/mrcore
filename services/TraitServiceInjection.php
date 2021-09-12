<?php declare(strict_types=1);
namespace mrcore\services;
use RuntimeException;

require_once 'mrcore/services/InterfaceInjectableService.php';

/**
 * Реализация метода внедрения сервисов в различные объекты.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/services
 * @uses       $GLOBALS['MRCORE_ISC_OBJECT']
 */
trait TraitServiceInjection
{
    /**
     * Массив описания сервисов, которыми может воспользоваться объект.
     *
     * @var    array [string => true|[source => string,
     *                                instanceof => string,
     *                                extraParams => string], ...]
     */
    private ?array $_subscribedServices = null;

    /**
     * Массыв ссылок на внедрённые сервисы в объект, которыми он может воспользоваться.
     *
     * @var    array [string => &InterfaceInjectableService, ...]
     */
    private array $_injectedServices = [];

    #################################### Methods #####################################

    /**
     * Возвращается массив описания сервисов, которыми может воспользоваться объект.
     *
     * @return   array [string => true|[source => string,
     *                                 instanceof => string,
     *                                 extraParams => string], ...]
     */
    abstract protected function _getSubscribedServices(): array;

    /**
     * Внедрение сервиса, получение ссылки на этот сервис.
     *
     * @param      string  $name
     * @param      bool   $forceCreate OPTIONAL
     * @param      bool   $throwIfNotExists OPTIONAL
     * @return     InterfaceInjectableService|null
     */
    public function &injectService(string $name, bool $forceCreate = false, bool $throwIfNotExists = true): ?InterfaceInjectableService
    {
        if (null === $this->_subscribedServices)
        {
            $this->_subscribedServices = $this->_getSubscribedServices();
        }

        if (isset($this->_injectedServices[$name]))
        {
            // только локальные объекты можно пересоздавать
            if (!$forceCreate || true === $this->_subscribedServices[$name])
            {
                return $this->_injectedServices[$name];
            }

            unset($this->_injectedServices[$name]);
        }

        if (isset($this->_subscribedServices[$name]))
        {
            // если это сервис из глобального пространства
            if (true === $this->_subscribedServices[$name])
            {
                $this->_injectedServices[$name] = &$GLOBALS['MRCORE_ISC_OBJECT']->get($name);
            }
            else
            {
                $this->_injectedServices[$name] = &$this->_createService($name);
            }

            return $this->_injectedServices[$name];
        }

        if ($throwIfNotExists)
        {
            throw new RuntimeException(sprintf('Injectable service "%s" is not found', $name));
        }

        return null;
    }

    ///**
    // * Запуск сервиса в качестве работы.
    // *
    // * @param      string  $name
    // * @param      array  $data
    // * @param      Closure|null  $cb ($success)
    // * @param      bool   $forceCreate OPTIONAL
    // */
    //public function execJob($name, array &$data, Closure $cb = null, bool $forceCreate = false): void
    //{
    //    if (($component = &$this->injectService($name, $forceCreate)))
    //    {
    //        $component->exec($data, $cb);
    //    }
    //}

    ///**
    // * Разрыв ссылки на внедрённый сервис.
    // *
    // * @param      string $name
    // */
    //protected function _unlinkService(string $name): void
    //{
    //    unset($this->_injectedServices[$name]);
    //}

    /**
     * Разрыв ссылок на все внедрённые сервисы.
     */
    protected function _unlinkAllServices(): void
    {
        $this->_injectedServices = [];
    }

    /**
     * Создание локального сервиса, необходимого только данному объекту.
     *
     * @param      string $name
     * @return     InterfaceInjectableService
     */
    private function &_createService(string $name): InterfaceInjectableService
    {
        $meta = $this->_subscribedServices[$name];
        $source = $meta['source'];

        require_once strtr(ltrim($source, '\\'), '\\', '/') . '.php';

        $result = new $source($name, $this, $serviceMeta['extraParams'] ?? []);

        if (!empty($meta['extendClass']) && !($result instanceof $meta['extendClass']))
        {
            trigger_error(sprintf('Service %s (%s) is not instance of %s', $name, $source, $meta['extendClass']), E_USER_ERROR);
        }

        return $result;
    }

}