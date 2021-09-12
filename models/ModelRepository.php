<?php declare(strict_types=1);
namespace mrcore\models;
use RuntimeException;
use mrcore\db\Adapter;
use mrcore\exceptions\DbException;
use mrcore\services\TraitServiceInjection;

require_once 'mrcore/services/TraitServiceInjection.php';

/**
 * Репозиторий модельных объектов.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/models
 */
class ModelRepository
{
    use TraitServiceInjection;

    /**
     * Ссылка на соединение с БД.
     */
    protected Adapter $_connDb;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _getSubscribedServices(): array
    {
        return array
        (
            'global.connection' => true, // :TODO: или ввести dbName как отдельный параметр или в 'global.connection' добавить свойство dbName
        );
    }

    /**
     * Конструктор класса.
     *
     * @param      array $params [string => mixed, ...] OPTIONAL
     */
    public function __construct(array $params = [])
    {
        /* @var $connDb Adapter */
        $this->_connDb = &$this->injectService('global.connection')->db();
    }

    /**
     * Создаётся и возвращается указанный модельный объект.
     *
     * @param      string  $source
     * @param      mixed   ...$params
     * @return     AbstractModel
     * @throws     RuntimeException
     */
    public function &createModel($source, ...$params): AbstractModel
    {
        require_once strtr(ltrim($source, '\\'), '\\', '/') . '.php';

        if (!class_exists($source, false))
        {
            throw new RuntimeException(sprintf('Class %s is not found', $source));
        }

        $model = new $source(...$params);

        return $model;
    }

    /**
     * Возвращается указанный модельный объект с загруженными данными.
     *
     * @param      string  $source
     * @param      int     $objectId
     * @return     AbstractModel
     * @throws     DbException
     */
    public function getModel($source, int $objectId): AbstractModel
    {
        $model = &$this->createModel($source);

        if (!$model->load($objectId))
        {
            require_once 'mrcore/exceptions/DbException.php';
            throw new DbException(sprintf('Object %u of class %s is not loaded', $objectId, $source));
        }

        return $model;
    }

}