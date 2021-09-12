<?php declare(strict_types=1);
namespace mrcore\db;

/**
 * Класс для задания sql выражений, которые не нужно экранировать (например: NOW())
 * в методах execQuery, fetch..., insert, update класса \mrcore\db\Adapter.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/db
 */
class HelperExpr
{
    /**
     * SQL выражение.
     *
     * @var    string
     */
    private string $_value;

    /**
     * SQL выражение.
     *
     * @var    mixed
     */
    private $_bind;

    #################################### Methods #####################################

    /**
     * Возвращается NULL выражение, если $value пустое.
     *
     * @param      string  $value
     * @return     HelperExpr|string
     */
    public static function nullIfEmpty(string $value)
    {
        return ('' === $value ? new HelperExpr('NULL') : $value);
    }

    /**
     * Конструктор класса.
     *
     * @param      string  $value
     * @param      mixed  $bind OPTIONAL
     */
    public function __construct(string $value, $bind = [])
    {
        $this->_value = $value;
        $this->_bind = $bind;
    }

    /**
     * Получение SQL выражения.
     *
     * @param      Adapter  $conn
     * @return     string
     */
    public function get(Adapter $conn): string
    {
        if (!empty($this->_bind))
        {
            $this->_value = $conn->bind($this->_value, $this->_bind);
            $this->_bind = [];
        }

        return $this->_value;
    }

}