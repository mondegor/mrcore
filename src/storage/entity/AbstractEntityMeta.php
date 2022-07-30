<?php declare(strict_types=1);
namespace mrcore\storage\entity;
use mrcore\debug\Assert;
use mrcore\storage\entity\helper\AbstractMetaHelper;

/**
 * Абстракция метаданных сущности, которая описывает структуру её отображения в хранилище данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_MetaHelper=AbstractMetaHelper
 *
 * @template  T_ENTITYMETA_FIELD=array{dbName: string OPTIONAL, // название поля в таблице хранилища
 *                                     type: int, // тип значения поля {@see EnumType::STRING}
 *                                     ?default: string|int|float|bool|T_ARRAY|null, // значение по умолчанию при создании сущности
 *                                     ?length: int, // максимальная длина поля
 *                                     ?nullable: int, // может ли свойство принимать NULL значения  {@see AbstractEntityMeta::NULL_TRUE}
 *                                     ?list: string[]} // массив возможных значений для типов ENUM и ESET
 */
abstract class AbstractEntityMeta
{
    /**
     * Поддержка NULL значения поля сущности.
     */
    public const NULL_FALSE = 0, // null значения запрещены
                 NULL_TRUE  = 1, // null значения разрешены
                 NULL_TRUE_CAST_EMPTY = 2; // null значения разрешены + пустые значения будут преобразовываться в NULL значение

    ################################### Properties ###################################

    /**
     * Таблица связанная с сущностью.
     */
    /*__abstract__*/ protected string $tableName;

    /**
     * Название первичного ключа сущности.
     */
    /*__abstract__*/ protected string $primaryName;

    /**
     * Массив зарегистрированных полей сущности.
     *
     * @var  array<string, T_ENTITYMETA_FIELD>
     */
    /*__abstract__*/ public array $fields;

    #################################### Methods #####################################

    /**
     * Создание вспомогательного объекта для работы с полями сущности.
     *
     * @param  class-string<T_MetaHelper> $class
     * @return T_MetaHelper
     */
    public function createHelper(string $class): AbstractMetaHelper
    {
        assert(Assert::instanceOf($class, AbstractMetaHelper::class), Assert::instanceOfMessage($class, AbstractMetaHelper::class));

        return new $class($this);
    }

    /**
     * Возвращается таблица сущности.
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Возвращается название первичного ключа сущности.
     */
    public function getPrimaryName(): string
    {
        return $this->primaryName;
    }

    /**
     * Возвращается название первичного ключа в БД.
     */
    public function getDbPrimaryName(): string
    {
        return $this->getDbName($this->primaryName);
    }

    /**
     * Возвращается название поля в БД.
     */
    public function getDbName(string $name): string
    {
        assert(isset($this->fields[$name]['dbName']));

        return $this->fields[$name]['dbName'];
    }

}