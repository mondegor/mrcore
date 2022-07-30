<?php declare(strict_types=1);
namespace mrcore\storage\exceptions;
use RuntimeException;

/**
 * Exception: класс для формирования информации об ошибках связанной с {@see AbstractEntityMeta}.
 *
 * @author  Andrey J. Nazarov
 */
class EntityMetaException extends RuntimeException
{

    public static function fieldNotSupportedDefaultValue(string $metaClass, string $fieldName): self
    {
        return new self
        (
            sprintf
            (
                'Поле %s::$fields[%s] не поддерживает значение по умолчанию',
                $metaClass, $fieldName
            )
        );
    }

    public static function fieldNotSupportedValue(string $metaClass, string $fieldName, string $value): self
    {
        return new self
        (
            sprintf
            (
                'Поле %s::$fields[%s] не поддерживает значение %s',
                $metaClass, $fieldName, $value
            )
        );
    }

    public static function primaryIdIsZero(string $metaClass): self
    {
        return new self
        (
            sprintf
            (
                'The primary ID of entity %s is zero after inserting this entity',
                $metaClass
            )
        );
    }

    public static function noPropertyIsInited(string $metaClass): self
    {
        return new self
        (
            sprintf
            (
                'No property is inited for entity %s',
                $metaClass
            )
        );
    }

    public static function primaryKeyNullOrNotFound(string $metaClass, string $primaryName): self
    {
        return new self
        (
            sprintf
            (
                'The primary key %s is NULL or not found in the entity %s',
                $primaryName, $metaClass
            )
        );
    }

}