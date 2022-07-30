<?php declare(strict_types=1);
namespace mrcore\di;

/**
 * Интерфейс конфигураций Dependency Injection (ID).
 *
 * @author  Andrey J. Nazarov
 */
interface DependencyConfigInterface
{
    /**
     * Возвращается список соответствий классу объекта его класса-фабрики.
     *
     * @return  array<string, string|callable>
     */
    public static function getClassToFactory(): array;

}