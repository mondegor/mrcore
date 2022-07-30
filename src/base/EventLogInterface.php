<?php declare(strict_types=1);
namespace mrcore\base;

/**
 * Интерфейс инструмента фиксирования событий.
 *
 * @author  Andrey J. Nazarov
 */
interface EventLogInterface
{
    /**
     * Общие типы событий.
     */
    public const VISIT      =   1, // 'visit', // специальный тип используемый в фильтрах: "посещения пользователя"
                 SYSTEM_ALL = 101, // 'system',
                 NOTICE     =   2, // 'notice',
                 WARNING    =   3, // 'warning',
                 ERROR      =   4; // 'error';

    /**
     * Типы событий по операциям безопасности.
     */
    public const SECURITY_ALL = 102, // 'security', // специальный тип используемый в фильтрах: "операций по безопасности"
                 ALLOWED      =   5, // 'allowed',
                 DENIAL       =   6; // 'denial';

    /**
     * Типы событий об изменениях в БД.
     */
    public const DB_ALL = 103, // 'db', // специальный тип используемый в фильтрах: "операций БД"
                 QUERY  =   7, // 'query',
                 INSERT =   8, // 'insert',
                 UPDATE =   9, // 'update',
                 DELETE =  10; // 'delete';

    #################################### Methods #####################################

    /**
     * Добавление указанного события.
     *
     * @param  int          $eventType
     * @param  array|string $data string or [string, int, string] // [message, objectId, objectInfo]
     * @param  int|null     $userId OPTIONAL
     */
    public function add(int $eventType, array|string $data, int $userId = null): void;

}