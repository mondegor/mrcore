<?php declare(strict_types=1);
namespace mrcore\http;
use mrcore\base\EnumType;

/**
 * Абстракция обёртки для доступа к именованным данным
 * поступивших от клиента на сервер.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractClientData
{
    /**
     * Проверяется, существует ли переменная с указанным именем.
     */
    abstract public function has(string $name): bool;

    /**
     * Возвращает STRING значение параметра $name,
     * либо значение по умолчанию $default.
     */
    abstract public function get(string $name, string $default = null): string;

    /**
     * Устанавливает указанное значение параметра $name.
     */
    abstract public function set(string $name, string|int|float|bool|null|array $value): self;

    /**
     * Возвращается сырые данные.
     *
     * @return     array [string => [string|array, ...]
     */
    abstract public function getRaw(): array;

    ##################################################################################

    protected function _wrapCast(int $type, string|int|float|bool|null|array $value): string|int|float|array
    {
        return EnumType::cast($type, $value);
    }

    protected function _wrapConvert(string|array $value): string|array
    {
        return EnumType::convert($value);
    }

}