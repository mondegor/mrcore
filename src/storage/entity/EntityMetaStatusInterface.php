<?php declare(strict_types=1);
namespace mrcore\storage\entity;

/**
 * Интерфейс добавляет базовые возможности по управлению статусами сущности.
 * Есть поддержка пометки удаления вместо реального удаления.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ENTITYMETA_STATUS_FIELDS={statusField => string, // название поля статуса сущности
 *                                        datetimeField => string, // название поля даты изменения статуса сущности
 *                                        statusRemove => string} // значение статуса: сущность удалена
 */
interface EntityMetaStatusInterface
{
    /**
     * Возвращается метаданные описывающие поля статусов сущности.
     *
     * @return  T_ENTITYMETA_STATUS_FIELDS;
     */
    public function getStatusFields(): array;

}