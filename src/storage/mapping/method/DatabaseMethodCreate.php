<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method;
use mrcore\base\EventLogInterface;
use mrcore\storage\entity\helper\HelperPrepareProperties;
use mrcore\storage\entity\EntityInterface;
use mrcore\storage\exceptions\EntityMetaException;

/**
 * Абстракция создания указанной сущности в базе данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES_SIMPLE
 * @template  T_ARRAY_ITEM
 */
abstract class DatabaseMethodCreate extends DatabaseMethod
{
    /**
     * @inheritdoc
     */
    public function execute(EntityInterface $entity, array $params = null): bool
    {
        $definedNames = [];
        $fields = [];
        $values = [];
        $newProps = [];

        $meta = $entity->getMeta();

        foreach ($this->_getProperties($entity, $definedNames) as $name => $value)
        {
            $fields[] = $meta->getDbName($name);
            $values[] = $value;

            if (!in_array($name, $definedNames, true))
            {
                $newProps[$name] = $value;
            }
        }

        $result = $this->_insert
        (
            $meta->getTableName(),
            $fields,
            $values
        );

        if ($result)
        {
            $objectId = $entity->getId();

            if (null === $objectId)
            {
                $objectId = $this->_getLastInsertedId();

                if ($objectId < 1)
                {
                    throw EntityMetaException::primaryIdIsZero(get_class($meta));
                }

                $entity->setId($objectId);
            }

            $entity->setProperties($newProps);

            $this->_event
            (
                EventLogInterface::INSERT,
                [
                    'A new account has been added',
                    $objectId,
                    $meta->getTableName()
                ]
            );
        }

        return $result;
    }

    ##################################################################################

    /**
     * Подготавливаются все установленные ранее свойства сущности к вставке и затем возвращаются.
     *
     * @param   string[]  $definedNames
     * @return  T_PROPERTIES_SIMPLE
     */
    protected function _getProperties(EntityInterface $entity, array &$definedNames): array
    {
        $props = $entity->getProperties();
        $definedNames = array_keys($props);

        $props = $entity->getMeta()
                        ->createHelper(HelperPrepareProperties::class)
                        ->prepareInsert($props);

        if (empty($props))
        {
            throw EntityMetaException::noPropertyIsInited(get_class($entity->getMeta()));
        }

        return $props;
    }

    /**
     * Вставка указанной сущности в хранилище данных.
     *
     * @param  string[] $fields
     * @param  T_ARRAY_ITEM[] $values
     */
    abstract protected function _insert(string $tableName, array $fields, array $values): bool;

    /**
     * Возвращается последний вставленный идентификатор.
     */
    abstract protected function _getLastInsertedId(): int;

}