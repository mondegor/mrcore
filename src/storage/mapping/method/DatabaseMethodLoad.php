<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method;
use mrcore\storage\entity\helper\HelperStatusFields;
use mrcore\storage\entity\EntityInterface;

/**
 * Абстракция загрузки указанной сущности из хранилища данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ENTITYMETA_STATUS_FIELDS
 * @template  T_METHODLOAD_EXECUTE=array{names: string[]}
 */
abstract class DatabaseMethodLoad extends DatabaseMethod
{
    /**
     * @inheritdoc
     * @param   T_METHODLOAD_EXECUTE|null
     */
    public function execute(EntityInterface $entity, array $params = null): bool
    {
        assert(null === $params || (!empty($params['names']) && array_is_list($params['names'])));

        $meta = $entity->getMeta();
        $primaryName = $meta->getPrimaryName();
        $names = $params['names'] ?? array_keys($meta->fields);

        ##################################################################################

        $selectNames = [];

        foreach ($names as $name)
        {
            if (!isset($meta->fields[$name]))
            {
                trigger_error(sprintf('The property %s is not registered in the entity %s, so it will be skipped', $name, get_class($meta)), E_USER_NOTICE);
                continue;
            }

            if ($name !== $primaryName && $entity->hasProperty($name))
            {
                trigger_error(sprintf('The property %s was changed previously in the entity %s, the value will be lost', $name, get_class($meta)), E_USER_NOTICE);
            }

            $selectNames[] = sprintf('%s as %s', $meta->getDbName($name), $name);
        }

        ##################################################################################

        $row = $this->_fetchRow
        (
            $meta->getTableName(),
            $meta->getDbPrimaryName(),
            $selectNames,
            $entity->getId(),
            $meta->createHelper(HelperStatusFields::class)->getDbStatusFields()
        );

        if (null !== $row)
        {
            $entity->setProperties($row);

            return true;
        }

        return false;
    }

    ##################################################################################

    /**
     * Выборка указанного объекта из хранилища данных.
     *
     * @param  string[]  $selectNames
     * @param  T_ENTITYMETA_STATUS_FIELDS|null  $statusFields
     */
    abstract protected function _fetchRow(string $tableName,
                                          string $primaryName,
                                          array $selectNames,
                                          int|string $objectId,
                                          array $statusFields = null);

}