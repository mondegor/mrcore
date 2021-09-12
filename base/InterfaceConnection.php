<?php declare(strict_types=1);
namespace mrcore\base;

/**
 * Интерфейс для реализации различных соединений: БД, внешних API и т.д.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/base
 */
interface InterfaceConnection
{

    ##################################################################################
    # InterfaceConnection Members

    /**
     * Установлено ли соединение с ресурсом.
     *
     * @return     bool
     */
    public function isConnection(): bool;

    /**
     * Отключение от подключенного ресурса.
     */
    public function close(): void;

    # End InterfaceConnection Members
    ##################################################################################

}