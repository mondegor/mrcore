<?php declare(strict_types=1);
use mrcore\db\Adapter;
use mrcore\db\HelperExpr;

/**
 * Доступ к настройкам системы хранящимся в БД.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/services
 */
class SettingsService
{
    /**
     * Ссылка на соединение с БД.
     */
    private Adapter $_connDb;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      Adapter $connDb
     */
    public function __construct(Adapter $connDb)
    {
        $this->_connDb = &$connDb;
    }

    /**
     * Возвращение значения указанной глобальной настройки системы.
     * В следующем формате можно указать настройку вместе с её группой: group.settings
     *
     * @param      string  $name
     * @param      mixed  $default OPTIONAL
     * @return     int|string|array|null
     */
    public function get(string $name, $default = null)
    {
        $group = '';
        $value = null;

        if (false !== ($index = mb_strrpos($name, '.')))
        {
            $group = mb_substr($name, 0, $index);
            $name = mb_substr($name, $index + 1);
        }

        if (!($row = $this->_connDb->fetchRow("SELECT setting_value,
                                                      setting_type
                                               FROM mrcore_global_settings
                                               WHERE setting_group = ? AND setting_name = ?
                                               LIMIT 1", [$group, $name], false)))
        {
            return $default;
        }

        if ('array' === $row[1])
        {
            $value = empty($row[0]) ? [] : json_decode($row[0], true, 512, JSON_THROW_ON_ERROR);
        }
        else if ('bool' === $row[1])
        {
            $value = (bool)$row[0];
        }
        else if ('float' === $row[1])
        {
            $value = (float)$row[0];
        }
        else if ('int' === $row[1])
        {
            $value = (int)$row[0];
        }
        else //if ('string' == $row[1])
        {
            $value = $row[0];
        }

        return $value;
    }

    /**
     * Возвращение значений всех глобальных настроек системы указанной группы.
     *
     * @param      string  $name
     * @param      bool    $nocache OPTIONAL
     * @return     array [string => mixed, ...]
     */
    public function getGroup(string $name, bool $nocache = false): array
    {
        static $result = [];

        if (!isset($result[$name]) || $nocache)
        {
            $result[$name] = [];

            $resource = $this->_connDb->execQuery("SELECT setting_name,
                                                          setting_value,
                                                          setting_type
                                                   FROM mrcore_global_settings
                                                   WHERE setting_group = ?", $name);

            while ($row = $this->_connDb->fetch($resource, false))
            {
                if ('array' === $row[2])
                {
                    $result[$name][$row[0]] = empty($row[1]) ? [] : json_decode($row[1], true, 512, JSON_THROW_ON_ERROR);
                }
                else if ('bool' === $row[2])
                {
                    $result[$name][$row[0]] = (bool)$row[1];
                }
                else if ('float' === $row[2])
                {
                    $result[$name][$row[0]] = (float)$row[1];
                }
                else if ('int' === $row[2])
                {
                    $result[$name][$row[0]] = (int)$row[1];
                }
                else //if ('string' === $row[2])
                {
                    $result[$name][$row[0]] = $row[1];
                }
            }
        }

        return $result[$name];
    }

    /**
     * Возвращение значений указанных глобальных настроек системы.
     * Если все настройки из одной группы, то ключами результирущего массива будут имена настроек: 'name1',
     * иначе ключами результирущего массива станут имена групп + имена настроек: 'group1.name1'
     *
     * Пример параметров функции из одной группы: 'group1.name1', 'group1.name2', ...
     * Результат: ['name1' => 'value1', 'name2' => 'value2']
     *
     * Пример параметров функции из нескольких групп: 'group1.name1', 'group2.name2', ...
     * Результат: ['group1.name1' => 'value1', 'group2.name2' => 'value2']
     *
     * @param      $names [string, ...]
     * @return     array [string => mixed, ...]
     */
    public function getSome(...$names): array
    {
        if (empty($names))
        {
            return [];
        }

        $result = array_fill_keys($names, null);

        $where = [];
        $lastGroup = null;
        $isFullName = false;

        foreach ($names as $name)
        {
            $group = '';

            if (false !== ($index = mb_strrpos($name, '.')))
            {
                $group = mb_substr($name, 0, $index);
                $name = mb_substr($name, $index + 1);
            }

            $where[] = $this->_getExpr("(setting_group = ? AND setting_name = ?)", [$group, $name]);

            if (null !== $lastGroup && $lastGroup !== $group)
            {
                $isFullName = true;
            }

            $lastGroup = $group;
        }

        $resource = $this->_connDb->execQuery(sprintf("SELECT %s,
                                                              setting_value,
                                                              setting_type
                                                       FROM mrcore_global_settings
                                                       WHERE %s",
                                                           ($isFullName ? "CONCAT(setting_group, '.', setting_name)" : 'setting_name'),
                                                           implode(' OR ', $where)));

        while ($row = $this->_connDb->fetch($resource, false))
        {
            if ('array' === $row[2])
            {
                $result[$row[0]] = empty($row[1]) ? [] : json_decode($row[1], true, 512, JSON_THROW_ON_ERROR);
            }
            else if ('bool' === $row[2])
            {
                $result[$row[0]] = (bool)$row[1];
            }
            else if ('float' === $row[2])
            {
                $result[$row[0]] = (float)$row[1];
            }
            else if ('int' === $row[2])
            {
                $result[$row[0]] = (int)$row[1];
            }
            else //if ('string' == $row[2])
            {
                $result[$row[0]] = $row[1];
            }
        }

        return $result;
    }

    /**
     * Сохранение значения указанной глобальной настройки системы.
     *
     * @param      string  $name
     * @param      mixed  $value
     * @return     bool
     */
    public function set(string $name, $value): bool
    {
        $group = '';
        $type = 'string';

        if (false !== ($index = mb_strrpos($name, '.')))
        {
            $group = mb_substr($name, 0, $index);
            $name = mb_substr($name, $index + 1);
        }

        if (is_array($value))
        {
            $type = 'array';
            $value = empty($value) ? '' : json_encode($value, JSON_THROW_ON_ERROR, 512);
        }
        else if (is_bool($value))
        {
            $type = 'bool';
        }
        else if (is_float($value))
        {
            $type = 'float';
        }
        else if (is_int($value))
        {
            $type = 'int';
        }
        else if (null === $value)
        {
            $value = '';
        }

        $this->_connDb->execQuery("UPDATE mrcore_global_settings
                                   SET setting_type = ?,
                                       setting_value = ?,
                                       datetime_updated = NOW()
                                   WHERE setting_group = ? AND setting_name = ?", [$type, $value, $group, $name]);

        if (0 === $this->_connDb->getAffectedRows())
        {
            $this->_connDb->execQuery("INSERT INTO mrcore_global_settings
                                           (setting_group, setting_name, setting_type, setting_value, datetime_updated)
                                       VALUES
                                           (?, ?, ?, ?, NOW())", [$group, $name, $type, $value]);
        }

        return true;
    }

    /**
     * @see    HelperExpr::get()
     */
    /*__private__*/protected function _getExpr(string $value, $bind = [])
    {
        return (new HelperExpr($value, $bind))->get($this->_connDb);
    }

}