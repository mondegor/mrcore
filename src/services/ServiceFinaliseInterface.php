<?php declare(strict_types=1);
namespace mrcore\services;

/**
 * Интерфейс для реализации общих сервисов
 * с поддержкой метода завершения работы.
 *
 * @author  Andrey J. Nazarov
 */
interface ServiceFinaliseInterface extends ServiceInterface
{
    /**
     * Метод вызывается в конце работы приложения, для завершения работы встраиваемого сервиса.
     */
    public function finalise(): void;

}