<?php declare(strict_types=1);

/**
 * Класс описывает сущность "Соединение с ресурсами"
 * (соединение с хранилищами данных, внешними API и другими сущностями").
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrglobal
 */
class InjectedServicesContainer
{
    private array $_services =
    [
        //'global.app' => AppService::class,
        //'global.connection' => ConnService::class,
        //'global.env' => EnvService::class,
        //'global.response' => ResponseService::class,
        //'global.var' => VarService::class,
    ];

    function __construct(array $env = [])
    {
        // INJECTED_SERVICES_CONTAINER
        $GLOBALS['MRCORE_ISC_OBJECT'] = &$this;
    }

    function &get($name)
    {
        return $this->_services[$name];
    }

    function addService($name, $object)
    {
        $this->_services[$name] = &$object;
    }

}