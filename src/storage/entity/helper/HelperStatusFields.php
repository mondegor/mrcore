<?php declare(strict_types=1);
namespace mrcore\storage\entity\helper;
use mrcore\storage\entity\EntityMetaStatusInterface;

/**
 * Вспомогательный класс обращения к полям статуса {@see AbstractEntityMeta::$fields}
 * определённых в интерфейсе {@see EntityMetaStatusInterface}.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ENTITYMETA_STATUS_FIELDS
 */
class HelperStatusFields extends AbstractMetaHelper
{
    /**
     * Возвращаются все поля статуса, но их названия заменены на названия в БД.
     *
     * @return  T_ENTITYMETA_STATUS_FIELDS|null
     */
    public function getDbStatusFields(): array|null
    {
        if ($this->meta instanceof EntityMetaStatusInterface)
        {
            $statusFields = $this->meta->getStatusFields();

            return [
                'statusField' => $this->meta->getDbName($statusFields['statusField']),
                'datetimeField' => $this->meta->getDbName($statusFields['datetimeField']),
                'statusRemove' => $statusFields['statusRemove'],
            ];
        }

        return null;
    }

}