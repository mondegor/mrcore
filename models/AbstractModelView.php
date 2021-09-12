<?php
namespace mrcore\models;
use RuntimeException;
use mrcore\base\TraitFactory;

require_once 'mrcore/base/TraitFactory.php';

/**
 * Облегчённая версия модельного объекта классаAbstractModel,
 * его цель загрузить объект и передать в шаблонизатор.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/models
 */
abstract class AbstractModelView
{
    use TraitFactory; // defined method factory($source, $params)

    /**
     * Namespace по умолчанию используемой в TraitFactory::factory(),
     * для подстановки в $source если в нём не был указан свой namespace.
     *
     * @var string
     */
    private static string $_defaultNamespace = 'models';

    /**
     * Свойства страницы.
     *
     * @var    array
     */
    protected array $_props = [];

    /**
     * Идентификатор модельного объека.
     *
     * @var    int
     */
    private int $_itemId = 0;

    #################################### Methods #####################################

    /**
     * Получение модельного объекта с его инициализацией.
     *
     * @param      string  $source (Class) or (\package\Class)
     * @param      string $actionPath
     * @param      array  $params OPTIONAL [string => mixed, ...]
     * @return     AbstractModelView
     * @throws     RuntimeException
     */
    public static function &get(string $source, string $actionPath, array $params = []): AbstractModelView
    {
        /* @var AbstractModelView $page */
        $page = &self::factory($source, $params);
        $page->load($actionPath);

        return $page;
    }

    /**
     * Конструктор класса.
     *
     * @param      array  $props
     */
    public function __construct(array $props)
    {
        $this->_props = $props;
    }

    /**
     * Возвращается идентификатор ресурса.
     *
     * @return     int
     */
    public function getId(): int
    {
        return $this->_itemId;
    }

    /**
     * Устанавливается указанное значение указанного свойства.
     *
     * @param   string $name
     * @param   mixed $value
     */
    public function setProperty(string $name, $value): void
    {
        $this->_props[$name] = $value;
    }

    /**
     * Возвращается указанное свойство ресурса.
     *
     * @param      string  $name
     * @return     mixed
     */
    public function getProperty(string $name)
    {
        assert(isset($this->_props[$name])/* && 'contentCached' !== $name*/);

        return $this->_props[$name] ?? '';
    }

    /**
     * Загрузка ресурса из БД по его пути.
     *
     * @param      string  $actionPath
     * @return     bool
     */
    abstract public function load(string $actionPath): bool;

}