<?php declare(strict_types=1);
namespace mrcore\storage\entity;
use mrcore\base\EnumType;

/**
 * Реализация сущности отображаемой в хранилище данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES
 */
class Entity implements EntityInterface
{
    /**
     * Свойства сущности.
     *
     * @var  T_PROPERTIES
     */
    protected array $props = [];

    #################################### Methods #####################################

    /**
     * @param  T_PROPERTIES|null $props
     */
    public function __construct(protected AbstractEntityMeta $meta, array $props = null)
    {
        if (null !== $props)
        {
            $this->setProperties($props);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMeta(): AbstractEntityMeta
    {
        return $this->meta;
    }

    /**
     * @inheritdoc
     */
    public function getId(): int|string|null
    {
        return $this->props[$this->meta->getPrimaryName()] ?: null;
    }

    /**
     * @inheritdoc
     */
    public function setId(int|string $id): static
    {
        $this->props[$this->meta->getPrimaryName()] = $id;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasProperty(string $name): bool
    {
        return array_key_exists($name, $this->props);
    }

    /**
     * @inheritdoc
     */
    public function getProperty(string $name): string|int|float|bool|array|null
    {
        assert(isset($this->meta->fields[$name]));

        if (!array_key_exists($name, $this->props))
        {
            trigger_error(sprintf('The property %s is not loaded in the entity, so it will be returned NULL', $name), E_USER_NOTICE);
            return null;
        }

        return $this->props[$name];
    }

    /**
     * @inheritdoc
     */
    public function getProperties(array $names = null): array
    {
        if (null === $names)
        {
            return $this->props;
        }

        $result = [];

        foreach ($names as $alias => $name)
        {
            if (!array_key_exists($name, $this->props))
            {
                trigger_error(sprintf('The property %s is not loaded in the entity, so it will be skipped', $name), E_USER_NOTICE);
                continue;
            }

            $result[is_string($alias) ? $alias : $name] = $this->props[$name];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function setProperty(string $name, string|int|float|bool|array|null $value): static
    {
        $this->setProperties([$name => $value]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperties(array $props): static
    {
        foreach ($props as $name => $value)
        {
            if (!isset($this->meta->fields[$name]))
            {
                trigger_error(sprintf('The added property %s is not registered in the entity, so it will be skipped', $name), E_USER_NOTICE);
                continue;
            }

            // if value is null
            if (!empty($this->meta->fields[$name]['nullable']) && null === $value)
            {
                $this->props[$name] = $value;
                continue;
            }

            $this->props[$name] = EnumType::cast($this->meta->fields[$name]['type'], $value);
        }

        return $this;
    }

}