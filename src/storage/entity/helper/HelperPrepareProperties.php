<?php declare(strict_types=1);
namespace mrcore\storage\entity\helper;
use mrcore\base\EnumType;
use mrcore\storage\exceptions\EntityMetaException;

/**
 * Вспомогательный класс подготавливающий
 * указанные свойства к сохранению в хранилище данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ARRAY
 * @template  T_PROPERTIES
 * @template  T_PROPERTIES_SIMPLE
 */
class HelperPrepareProperties extends AbstractMetaHelper
{
    /**
     * Подготовка указанных свойств для вставки их в хранилище данных.
     *
     * @param  T_PROPERTIES $props
     * @return  T_PROPERTIES_SIMPLE
     */
    public function prepareInsert(array $props): array
    {
        $helper = $this->meta->createHelper(HelperMetaField::class);

        foreach ($helper->getNames() as $name)
        {
            $helper->set($name);

            if (!array_key_exists($name, $props))
            {
                if (!$helper->hasDefault())
                {
                    throw EntityMetaException::fieldNotSupportedDefaultValue(get_class($this->meta), $name);
                }

                $props[$name] = $helper->getDefault();
            }

            $props[$name] = $this->_prepareValue($helper, $props[$name]);
        }

        return $props;
    }

    /**
     * Подготовка указанных свойств для сохранения их в хранилище данных.
     *
     * @param   T_PROPERTIES $props
     * @return  T_PROPERTIES_SIMPLE
     */
    public function prepareUpdate(array $props): array
    {
        $helper = $this->meta->createHelper(HelperMetaField::class);

        foreach ($props as $name => $value)
        {
            $helper->set($name);
            $props[$name] = $this->_prepareValue($helper, $value);
        }

        return $props;
    }

    ##################################################################################

    /**
     * Преобразуется указанное значение к значению для
     * сохранения его в хранилище данных и возвращается результат.
     *
     * @param  string|int|float|bool|T_ARRAY|null $value
     */
    protected function _prepareValue(HelperMetaField $helper, string|int|float|bool|array|null $value): string|int|float|bool|null
    {
        if (null === $value)
        {
            if (!$helper->isNullable())
            {
                throw EntityMetaException::fieldNotSupportedValue(get_class($this->meta), $helper->getName(), 'NULL');
            }

            return null;
        }

        ##################################################################################

        $type = $helper->dbType();

        if (EnumType::BOOL === $type)
        {
            if (is_bool($value))
            {
                return $value;
            }
        }
        else if (EnumType::INT === $type)
        {
            if (is_int($value))
            {
                return $value;
            }
        }
        else if (EnumType::FLOAT === $type)
        {
            if (is_float($value))
            {
                return $value;
            }

            if (is_int($value))
            {
                return (float)$value;
            }
        }
        else if (EnumType::STRING === $type ||
                 EnumType::DATETIME === $type ||
                 EnumType::DATE === $type ||
                 EnumType::TIME === $type)
        {
            if (is_string($value))
            {
                if ('' === $value && $helper->isEmptyToNull())
                {
                    return null;
                }

                if (($maxLength = $helper->getMaxLength()) > 0 &&
                    mb_strlen($value) > $maxLength)
                {
                    $value = mb_substr($value, 0, $maxLength);

                    trigger_error
                    (
                        sprintf
                        (
                            'The value of the field %s::$fields[%s] has been truncated to %u characters',
                            get_class($this->meta), $helper->getName(), $maxLength
                        ),
                        E_USER_NOTICE
                    );
                }

                return $value;
            }
        }
        else if (EnumType::ENUM === $type)
        {
            if (is_string($value))
            {
                if ('' === $value)
                {
                    if (!$helper->isEmptyToNull())
                    {
                        throw EntityMetaException::fieldNotSupportedValue(get_class($this->meta), $helper->getName(), 'EMPTY');
                    }

                    return null;
                }

                if (!in_array($value, $helper->getList(), true))
                {
                    throw EntityMetaException::fieldNotSupportedValue(get_class($this->meta), $helper->getName(), $value);
                }

                return $value;
            }
        }
        else if (EnumType::ARRAY === $type)
        {
            if (is_array($value))
            {
                if (empty($value))
                {
                    return $helper->isEmptyToNull() ? null : ''; // :TODO: протестить
                }

                return json_encode($value);
            }
        }
        else if (EnumType::ESET === $type)
        {
            if (is_array($value))
            {
                if (empty($value))
                {
                    return $helper->isEmptyToNull() ? null : ''; // :TODO: протестить
                }

                $extra = array_diff($value, $helper->getList());

                if (!empty($extra))
                {
                    throw EntityMetaException::fieldNotSupportedValue(get_class($this->meta), $helper->getName(), implode(',', $extra));
                }

                return implode(',', $value);
            }
        }
        // else if (EnumType::IP === $type)
        // else if (EnumType::IPLONG === $type)

        throw EntityMetaException::fieldNotSupportedValue
        (
            get_class($this->meta),
            $helper->getName(),
            sprintf('dbType=%s; type=%s; value=%s', $helper->dbType(), gettype($value), $value)
        );
    }

}