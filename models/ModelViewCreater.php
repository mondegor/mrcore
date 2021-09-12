<?php
namespace mrcore\models;

/**
 * Облегчённая версия модельного объекта класса Model, его цель загрузить объект.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/models
 */
abstract class ModelViewCreater
{

    /**
     * Получение реального пути к экшену.
     *
     * @param      string  $actionPath
     * @return     string
     */
    private function _getPath($actionPath)
    {
        /*__assert__*/ assert('is_string($actionPath); // VALUE is not a string');

        $actionType = MrApp::$curAction->getActionType();
        $result = (Action::TYPE_SYSPAGE == $actionType ||
                   Action::TYPE_ERRORPAGE == $actionType ? '' : MrApp::$curDoc['path']);

        return $result;
    }

}