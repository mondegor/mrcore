<?php declare(strict_types=1);
namespace mrcore\di;

/**
 * Интерфейс для реализации поддержки завершения работы
 * объекта при завершении работы приложения.
 *
 * @author  Andrey J. Nazarov
 */
interface ObjectFinaliseInterface
{
    /**
     * Метод завершения работы объекта.
     */
    public function finalise(): void;

}