<?php declare(strict_types=1);
namespace mrcore\storage\entity\helper;
use mrcore\base\EnumType;
use mrcore\storage\entity\AbstractEntityMeta;

/**
 * Вспомогательный класс обращения к метаданным сущности {@see AbstractEntityMeta::$fields} .
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ARRAY
 * @template  T_ENTITYMETA_FIELD
 */
class HelperMetaField extends AbstractMetaHelper
{
    /**
     * Текущее обрабатываемое поле.
     */
    private string $name;

    /**
     * Поле текущего обрабатываемого поля.
     *
     * @var  T_ENTITYMETA_FIELD
     */
    private array $field;

    #################################### Methods #####################################

    /**
     * Возвращаются все названия полей сущности.
     *
     * @return   string[]
     */
    public function getNames(): array
    {
        return array_keys($this->meta->fields);
    }

    /**
     * Установка указанного поля в качестве текущего.
     */
    public function set(string $name): void
    {
        assert(isset($this->meta->fields[$name]));

        $this->name = $name;
        $this->field = &$this->meta->fields[$name];
    }

    /**
     * Возвращается название текущего поля.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Возвращается тип поля в хранилище данных.
     */
    public function dbType(): int
    {
        assert(isset($this->field['type'], EnumType::NAMES[$this->field['type']]));

        return $this->field['type'];
    }

    /**
     * Возвращается название поля в хранилище данных.
     */
    public function dbName(): string
    {
        assert(isset($this->field['dbName']));

        return $this->field['dbName'];
    }

    /**
     * Проверяется, имеется ли у поля значение по умолчанию.
     */
    public function hasDefault(): bool
    {
        return array_key_exists('default', $this->field);
    }

    /**
     * Возвращается значение по умолчанию.
     *
     * @return  string|int|float|bool|T_ARRAY|null
     */
    public function getDefault(): string|int|float|bool|array|null
    {
        return $this->field['default'] ?? null;
    }

    /**
     * Можно ли в поле записывать NULL значения.
     */
    public function isNullable(): bool
    {
        assert(!isset($this->field['nullable']) || is_int($this->field['nullable']));

        return !empty($this->field['nullable']);
    }

    /**
     * Можно ли пустое значение поля преобразовывать в NULL значение.
     */
    public function isEmptyToNull(): bool
    {
        assert(!isset($this->field['nullable']) || is_int($this->field['nullable']));

        return isset($this->field['nullable']) &&
               AbstractEntityMeta::NULL_TRUE_CAST_EMPTY === $this->field['nullable'];
    }

    /**
     * Возвращается максимальная допустимая длина поля,
     * и null если такого параметра не существует.
     */
    public function getMaxLength(): int
    {
        return $this->field['length'] ?? 0;
    }

    /**
     * Возвращается список возможных полей для ENUM и ESET.
     *
     * @return  string[]
     */
    public function getList(): array
    {
        return empty($this->field['list']) ? [] : $this->field['list'];
    }

}