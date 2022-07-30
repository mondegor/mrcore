<?php declare(strict_types=1);
namespace mrcore\storage\db;

/**
 * Класс для задания sql выражений, которые не нужно экранировать (например: NOW())
 * в методах execQuery, fetch..., insert, update класса mrcore\storage\db\AbstractDatabase.
 *
 * @author  Andrey J. Nazarov
 */
class HelperExpression
{
    /**
     * SQL выражение.
     */
    private string $_value;

    /**
     * Данные для SQL выражения.
     *
     * @var    array|null [string => mixed, ...]
     */
    private ?array $_bind;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      string  $value
     * @param      array|null  $bind [string => mixed, ...]
     */
    public function __construct(string $value, array $bind = null)
    {
        $this->_value = $value;
        $this->_bind = $bind;
    }

    /**
     * Возвращается SQL выражение.
     *
     * @param      AbstractDatabase  $db
     * @return     string
     */
    public function get(AbstractDatabase $db): string
    {
        if (null !== $this->_bind)
        {
            $this->_value = $db->bind($this->_value, $this->_bind);
            $this->_bind = null;
        }

        return $this->_value;
    }

}