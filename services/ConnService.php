<?php declare(strict_types=1);
namespace mrcore\services;
use Exception;
use mrcore\db\Adapter;

require_once 'mrcore/services/InterfaceInjectableService.php';

/**
 * Класс описывает сущность "Соединение с ресурсами"
 * (соединение с хранилищами данных, внешними API и другими сущностями").
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrglobal
 */
class ConnService implements InterfaceInjectableService
{
    /**
     * Массив открытых соединений.
     *
     * @var    array
     */
    private array $_conns = [];

    /**
     * Названия текущих соединений разных типов.
     *
     * @var    array
     */
    private array $_names = ['db' => 'default'];

    #################################### Methods #####################################

    /**
     * Возвращение всех открытых соединений указанного типа.
     *
     * @param      string  $type
     * @return     array
     */
    public function all(string $type): array
    {
        return $this->_conns[$type] ?? [];
    }

    /**
     * Переключение указанного соединения на
     * соединение по умолчанию (для указанного типа).
     *
     * @param      string  $type
     * @param      string|int $connName
     * @return     bool
     */
    public function change(string $type, $connName): bool
    {
        assert(is_string($connName) || is_int($connName));

        if (isset($GLOBALS[MRCORE_G_CONN][$connName]))
        {
            $this->_names[$type] = $connName;
            return true;
        }

        return false;
    }

    /**
     * Получение ссылки на указанное соединение указанного типа.
     * Соединение должно быть зарегистрировано в $GLOBALS[MRCORE_G_CONN].
     *
     * @param      string  $type
     * @param      string|int $connName OPTIONAL
     * @return     mixed
     */
    public function &get(string $type, $connName = '')
    {
        assert(is_string($connName) || is_int($connName));

        if (empty($connName))
        {
            if (!isset($this->_names[$type]))
            {
                trigger_error(sprintf('The default connection for type %s is not found', $type), E_USER_ERROR);
            }

            $connName = $this->_names[$type];
        }
        // если коннектер явно указан, то для этого типа нет коннетера по умолчанию,
        // то данный коннектер устанавливается по умолчанию
        else if (!isset($this->_names[$type]))
        {
            $this->_names[$type] = $connName;
        }

        return self::_get($type, $connName);
    }

    /**
     * Получение ссылки на указанное соединение с БД
     * (версия метода get предназначеная специально для БД).
     * Соединение должно быть зарегистрировано в $GLOBALS[MRCORE_G_CONN].
     *
     * @param      string $connName OPTIONAL
     * @return     Adapter
     */
    public function &db(string $connName = ''): Adapter
    {
        if ('' === $connName)
        {
            $connName = $this->_names['db'];
        }

        return self::_get('db', $connName);
    }

    /**
     * Закрытие указанного соединения и удаления его из кэша соединений.
     *
     * @param      string  $type
     * @param      string|int $connName OPTIONAL
     */
    public function close(string $type, string $connName = ''): void
    {
        if (empty($connName))
        {
            if (!isset($this->_names[$type]))
            {
                trigger_error(sprintf('The default connection for type %s is not found', $type), E_USER_ERROR);
            }

            $connName = $this->_names[$type];
        }

        ##################################################################################

        if (isset($this->_conns[$type][$connName]))
        {
            if (method_exists($this->_conns[$type][$connName], 'close'))
            {
                $this->_conns[$type][$connName]->close();
            }
            else
            {
                trigger_error(sprintf('The connection %s have not method close', $connName), E_USER_NOTICE);
            }

            unset($this->_conns[$type][$connName]);
        }
    }

    ##################################################################################

    /**
     * Возвращается следующее значение указанной последовательности.
     *
     * @param      string  $sequenceName
     * @return     int
     */
    public function getSequenceId(string $sequenceName): int
    {
        $connDb = &$this->db();
        $connDb->execQuery("UPDATE `mrcore_sequences`
                            SET `last_id` = LAST_INSERT_ID(`last_id` + 1)
                            WHERE `name` = ?", $sequenceName);

        if ($connDb->getAffectedRows() > 0)
        {
            $result = $connDb->getLastInsertedId();
        }
        else
        {
            $connDb->execQuery("INSERT INTO `mrcore_sequences`
                                    (`name`, `last_id`)
                                VALUES
                                    (?, 1)", $sequenceName);

            $result = 1;
        }

        return $result;
    }

    /**
     * Получение ссылки на указанное соединение указанного типа.
     * Соединение должно быть зарегистрировано в $GLOBALS[MRCORE_G_CONN].
     *
     * @param      string  $type
     * @param      string|int $connName
     * @return     mixed
     */
    private function &_get(string $type, $connName)
    {
        if (!isset($this->_conns[$type][$connName]))
        {
            $settings = array();

            if (isset($GLOBALS[MRCORE_G_CONN_TYPES][$type]))
            {
                $classMethod = $GLOBALS[MRCORE_G_CONN_TYPES][$type];
                require_once strtr(ltrim($classMethod[0], '\\'), '\\', '/') . '.php';

                if (!($settings = $classMethod($connName)))
                {
                    trigger_error(sprintf('The connection %s is not found in %s', $connName, implode('::', $classMethod)), E_USER_ERROR);
                }

                $connName = $settings['name'];
                // unset($_params['name']);
            }

            if (!isset($GLOBALS[MRCORE_G_CONN][$connName]))
            {
                trigger_error(sprintf('The connection %s is not registered in MRCORE_G_CONN', $connName), E_USER_ERROR);
            }

            $connSource = $GLOBALS[MRCORE_G_CONN][$connName]['source'];
            require_once strtr(ltrim($connSource, '\\'), '\\', '/') . '.php';
            $params = array_replace($GLOBALS[MRCORE_G_CONN][$connName]['params'], $settings);

            try
            {
                $this->_conns[$type][$connName] = new $connSource($params);
            }
            catch (Exception $e)
            {
                // если скрипт запущен не из коносоли
                if ('cli' !== PHP_SAPI && isset($_SERVER['DOCUMENT_ROOT'], $_SERVER['REQUEST_URI']))
                {
                    require_once MRCORE_PROJECT_DIR_TEMPLATES . 'shared/system/error.conn.tpl.php';
                }

                $errorType = isset($params['connectError']) ? $params['connectError'] : E_USER_ERROR;
                trigger_error($e->getMessage(), $errorType);
            }
        }

        return $this->_conns[$type][$connName];
    }

}