<?php declare(strict_types=1);
namespace mrcore\storage;
use mrcore\exceptions\UnableConnectionException;

/**
 * Интерфейс для реализации различных соединений с ресурсами: БД, внешних API и т.д.
 *
 * @author  Andrey J. Nazarov
 */
interface ConnectionInterface
{
    /**
     * Возвращается провайдер соединения (не зависит от установки соединения).
     */
    public function getProvider(): string;

    /**
     * Установка соединения с ресурсом.
     *
     * @throws  UnableConnectionException
     */
    public function open(): void;

    /**
     * Установлено ли соединение с ресурсом.
     */
    public function isConnection(): bool;

    /**
     * Разрыв соединения с ресурсом.
     */
    public function close(): void;

}