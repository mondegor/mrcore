<?php declare(strict_types=1);
namespace mrcore\models;
use mrcore\exceptions\DbException;
use mrcore\services\InterfaceInjectableService;

require_once 'mrcore/services/InterfaceInjectableService.php';

/**
 * Базовый класс компонента используемого в модельных объектах.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/models
 */
abstract class AbsrtactModelService implements InterfaceInjectableService
{
    /**
     * Ссылка на модельный объект.
     *
     * @var    AbstractModel
     */
    protected AbstractModel $_model;

    /**
     * Свойства, которые необходимо загрузить перед различными
     * операциями модельного объекта.
     *
     * @var    array
     */
    protected array $_preloadFields = [];

    /**
     * Загруженные свойства модели объекта.
     * В данный массив попадают те свойства, названия
     * которых описаны в $_preloadFields.
     * Если у модели объекта не задан идентификатор,
     * то свойства объекта инициализированны не будут.
     *
     * @var    array
     */
    protected array $_props = [];

    /**
     * Флаг контроля создания модельного компонента
     * только при инициализированном модельном объекте.
     *
     * @var    bool
     */
    protected bool $_onlyModelLoaded = true;

    /**
     * Название компонента.
     *
     * @var    string
     */
    private string $_name;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      string  $name
     * @param      AbstractModel  $model
     * @param      array  $params OPTIONAL
     * @throws     DbException
     */
    public function __construct(string $name, AbstractModel $model, array $params = [])
    {
        $this->_name = $name;
        $this->_model = &$model;

        $this->_init();

        if ($model->getId() > 0)
        {
            if (!empty($this->_preloadFields))
            {
                $this->_props = $model->getProperties($this->_preloadFields);
            }
        }
        else if ($this->_onlyModelLoaded)
        {
            require_once 'mrcore/exceptions/DbException.php';
            throw new DbException('Невозможно создать модельный сервис, т.к. не был указан идентификатор модельного объекта');
        }
    }

    /**
     * Возвращается идентификатор модели объекта.
     *
     * @return     int
     */
    final public function getModelId(): int
    {
        return $this->_model->getId();
    }

    /**
     * Возвращение имени компонента.
     *
     * @return     string
     */
    final public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Инициализация модельного компонента, вызывается во время работы конструктора.
     */
    protected function _init(): void {}

    /**
     * По названию поля модельного объекта возвращается название поля БД.
     *
     * @param      string  $name
     * @return     string
     */
    protected function _dbName(string $name): string
    {
        if (!($meta = $this->_model->getFieldMeta($name)) || empty($meta['dbName']))
        {
            return '';
        }

        $name = $meta['dbName'];

        if (false !== ($index = strpos($name, '.')))
        {
            $name = substr($name, $index + 1);
        }

        return $name;
    }

}