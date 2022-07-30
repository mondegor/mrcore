<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method;
use mrcore\base\EventLogInterface;
use mrcore\storage\entity\helper\HelperPrepareProperties;
use mrcore\storage\entity\helper\HelperStatusFields;
use mrcore\storage\entity\EntityInterface;
use mrcore\storage\exceptions\EntityMetaException;

/**
 * Абстракция сохранения указанной сущности в хранилище данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ENTITYMETA_STATUS_FIELDS
 * @template  T_ARRAY_ITEM
 * @template  T_PROPERTIES_SIMPLE
 */
abstract class DatabaseMethodStore extends DatabaseMethod
{
    /**
     * @inheritdoc
     */
    public function execute(EntityInterface $entity, array $params = null): bool
    {
        assert(null === $params);

        if (null === ($props = $this->_getProperties($entity)))
        {
            return false;
        }

        $fields = [];
        $values = [];

        $meta = $entity->getMeta();

        foreach ($props as $name => $value)
        {
            $fields[] = $meta->getDbName($name);
            $values[] = $value;
        }

        $result = $this->_update
        (
            $meta->getTableName(),
            $meta->getDbPrimaryName(),
            $entity->getId(),
            $fields,
            $values,
            $meta->createHelper(HelperStatusFields::class)->getDbStatusFields()
        );

        if ($result)
        {
            $this->_event
            (
                EventLogInterface::UPDATE,
                [
                    'The entity has been saved',
                    $entity->getId(),
                    $meta->getTableName()
                ]
            );
        }

        return $result;
    }

    ##################################################################################

    /**
     * Подготавливаются все установленные ранее свойства сущности к сохранению
     * (исключая первичный ключ) и затем возвращаются.
     *
     * @return  T_PROPERTIES_SIMPLE|null
     */
    protected function _getProperties(EntityInterface $entity): array|null
    {
        $meta = $entity->getMeta();
        $props = $entity->getProperties();
        $primaryName = $meta->getPrimaryName();

        if (!isset($props[$primaryName]))
        {
            throw EntityMetaException::primaryKeyNullOrNotFound(get_class($meta), $primaryName);
        }

        unset($props[$primaryName]);

        if (empty($props))
        {
            return null;
        }

        return $meta->createHelper(HelperPrepareProperties::class)
                    ->prepareUpdate($props);
    }

    /**
     * Обновление указанной сущности в хранилище данных.
     *
     * @param  string[] $fields
     * @param  T_ARRAY_ITEM[] $values
     * @param  T_ENTITYMETA_STATUS_FIELDS|null  $statusFields
     */
    abstract protected function _update(string $tableName,
                                        string $primaryName,
                                        int|string $objectId,
                                        array $fields,
                                        array $values,
                                        array $statusFields = null): bool;

}