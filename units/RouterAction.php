<?php declare(strict_types=1);
namespace mrcore\units;

require_once 'mrcore/units/AbstractAction.php';

/**
 * Системный экшен роутер - перенаправляет запросы.
 * Базовая версия для всех запросов генерирует 404 ошибку.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/units
 */
class RouterAction extends AbstractAction
{
    /**
     * Роутер является заглушкой, заруливает на 404 страницу.
     *
     * @inheritdoc
     */
    /*__override__*/ public function run(): int
    {
        return self::RESULT_NOT_FOUND;
    }

}