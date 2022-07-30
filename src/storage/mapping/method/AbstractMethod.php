<?php declare(strict_types=1);
namespace mrcore\storage\mapping\method;
use mrcore\base\EventLogInterface;
use mrcore\storage\ConnectionInterface;

// :TODO: EventLogInterface заменить на LoggerInterface

/**
 * Абстракция ORM метода взаимодействия сущности с хранилищем данных.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractMethod implements StorageMethodInterface
{
    /**
     * Инструмент фиксирования событий.
     */
    /*__abstract__*/ protected ?EventLogInterface $logger = null;

    #################################### Methods #####################################

    /**
     * @see EventLogInterface::add().
     */
    protected function _event(int $eventType, array|string $data): void
    {
        if (null !== $this->logger)
        {
            $this->logger->add($eventType, $data);
        }
    }

}