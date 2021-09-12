<?php declare(strict_types=1);
namespace mrcore\units;

/**
 * Базовой класс юнитов, которые система может создавать в автоматическом режиме.
 * Объекты наследуемые от данного класса имеют своё уникальное имя.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/units
 */
abstract class AbstractUnit
{
    /**
     * Глобальное имя юнита.
     *
     * @var    string
     */
    protected string $_unitName;

    /**
     * Глобальное имя модуля юнита.
     * Может быть явно задан в классе наследнике,
     * тогда он не будет вычисляться автоматически.
     *
     * @var    string|null
     */
    protected ?string $_moduleName = null;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      string  $unitName
     */
    public function __construct(string $unitName)
    {
        assert(preg_match('/^[a-z][a-z0-9\-_.]+[a-z0-9]$/i', $unitName) > 0);
        assert(trim($unitName, '.') === $unitName);
        assert(false === strpos($unitName, '..'));

        $this->_unitName = $unitName;

        if (null === $this->_moduleName)
        {
            $name = strstr($unitName, '.', true);
            $this->_moduleName = (false === $name ? $unitName : $name);
        }
    }

    /**
     * Возвращается название юнита.
     *
     * @return     string
     */
    public function getName(): string
    {
        return $this->_unitName;
    }

}