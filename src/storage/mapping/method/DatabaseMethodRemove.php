<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method;
use mrcore\base\EventLogInterface;
use mrcore\storage\entity\EntityInterface;
use mrcore\storage\entity\EntityMetaStatusInterface;

/**
 * Абстракция удаления указанной сущности из хранилища данных.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ENTITYMETA_STATUS_FIELDS
 * @template  T_METHODREMOVE_EXECUTE=array{?markAsRemoved: bool}
 */
abstract class DatabaseMethodRemove extends DatabaseMethod
{
    /**
     * @inheritdoc
     * @param  T_METHODREMOVE_EXECUTE
     */
    public function execute(EntityInterface $entity, array $params = null): bool
    {
        assert(null === $params || is_bool($params['markAsRemoved']));

        $meta = $entity->getMeta();
        $objectId = $entity->getId();

        if (!empty($params['markAsRemoved']))
        {
            if ($meta instanceof EntityMetaStatusInterface)
            {
                $statusFields = $meta->getStatusFields();

                $result = $this->_markAsRemoved
                (
                    $meta->getTableName(),
                    $meta->getDbPrimaryName(),
                    $meta->getDbName($statusFields['statusField']),
                    $meta->getDbName($statusFields['datetimeField']),
                    $objectId,
                    date('Y-m-d H:i:s'),
                    $statusFields['statusRemove']
                );

                if ($result)
                {
                    $this->_event
                    (
                        EventLogInterface::DELETE,
                        [
                            'The entity has been marked as removed',
                            $entity->getId(),
                            $meta->getTableName()
                        ]
                    );
                }

                return $result;
            }

            trigger_error(sprintf('Flag markAsRemoved is ignored for %s', get_class($meta)), E_USER_NOTICE);
        }

        $result = $this->_remove($meta->getTableName(), $meta->getDbPrimaryName(), $objectId);

        if ($result)
        {
            $this->_event
            (
                EventLogInterface::DELETE,
                [
                    'The entity has been removed',
                    $entity->getId(),
                    $meta->getTableName()
                ]
            );
        }

        return $result;
    }

    ##################################################################################

    /**
     * Удаление указанной сущности из хранилища данных.
     */
    abstract protected function _remove(string $tableName,
                                        string $primaryName,
                                        int|string $objectId): bool;

    /**
     * Пометка указанной сущности в качестве удалённой.
     */
    abstract protected function _markAsRemoved(string $tableName,
                                               string $primaryName,
                                               string $statusName,
                                               string $datetimeStatusName,
                                               int|string $objectId,
                                               string $statusValue,
                                               string $datetimeStatusValue): bool;

}