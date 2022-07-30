<?php declare(strict_types=1);
namespace mrcore\storage\entity;

/**
 * Интерфейс для реализации сущности отображаемой в хранилище данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ARRAY
 * @template  T_PROPERTIES
 */
interface EntityInterface
{
    /**
     * Возвращается метаданные сущности.
     */
    public function getMeta(): AbstractEntityMeta;

    /**
     * Возвращается идентификатор сущности.
     */
    public function getId(): int|string|null;

    /**
     * Установка идентификатора сущности.
     */
    public function setId(int|string $id): static;

    /**
     * Имеется ли значение указанного свойства у сущности.
     * (само поле метаданных может и быть, но значение отсутствует)
     */
    public function hasProperty(string $name): bool;

    /**
     * Возвращается значение указанного свойства сущности.
     *
     * @return  string|int|float|bool|T_ARRAY|null
     */
    public function getProperty(string $name): string|int|float|bool|array|null;

    /**
     * Возвращается значение указанных свойств сущности.
     * Если в массиве $names указать в качестве ключей строки (алиасы),
     * то при возвращении массива ключи будут сохранены:
     *     [alias => propName, ...] --> [alias => propValue]
     *
     * @param   array<string|int, string> $names
     * @return  T_PROPERTIES
     */
    public function getProperties(array $names = null): array;

    /**
     * Установка значения указанного свойства сущности.
     *
     * @param  string|int|float|bool|T_ARRAY|null $value
     */
    public function setProperty(string $name, string|int|float|bool|array|null $value): static;

    /**
     * Установка значений указанных свойств сущности.
     *
     * @param  T_PROPERTIES $props
     */
    public function setProperties(array $props): static;

}