<?php declare(strict_types=1);
namespace mrcore\base;

/**
 * Помощник контроля, что объект существует в единственном экземпляре.
 *
 * @author  Andrey J. Nazarov
 */
trait TraitSingleton
{
    /**
     * Был или уже создан экземпляр объекта.
     */
    private static bool $singletonIsCreated = false;

    #################################### Methods #####################################

    /**
     * Инициализация объекта в качестве Singleton.
     * Данный метод нужно вызывать в самом начале метода __construct().
     * Если объект уже был создан ранее, то будет ошибка.
     */
    private function _initSingleton(): void
    {
        if (false !== self::$singletonIsCreated)
        {
            trigger_error(sprintf('An object of class %s has already been created', __CLASS__), E_USER_ERROR);
        }

        self::$singletonIsCreated = true;
    }

    /**
     * Клонирование объектов запрещено.
     */
    private function __clone() { }

}