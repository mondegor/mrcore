<?php declare(strict_types=1);
namespace mrcore\models;

require_once 'mrcore/models/AbsrtactModelService.php';

/**
 * Базовый класс обработки указанного события.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/models
 */
abstract class AbsrtactEventHandler extends AbsrtactModelService
{
    /**
     * Выполнение обработчика события модельного объекта.
     *
     * @param      array  $eventArgs
     */
    abstract public function exec(array &$eventArgs);

}