<?php declare(strict_types=1);
namespace mrcore\models;
use mrcore\db\Adapter;
use mrcore\db\HelperExpr;
use mrcore\exceptions\DbException;
use mrcore\services\TraitServiceInjection;
use mrcore\services\VarService;

require_once 'mrcore/services/TraitServiceInjection.php';
require_once 'mrcore/services/VarService.php';

/**
 * Класс описывает сущность "Объектная модель".
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/models
 */
abstract class AbstractModel
{
    use TraitServiceInjection;

    /**
     * Признаки свойств модели.
     *
     * @var    int
     */
    public const TAG_HELPER = 0, // вспомагательное свойство для передачи его в методы create и store
                 TAG_COMPLEX = 1, // поле, которое состоит из нескольких других полей этой модели
                 TAG_CALCULATED = 2; // вычисляемое поле на основе других сущностей не входящих в модель

    /**
     * Тип события передаваемый в обработчик.
     *
     * @var    int
     */
    public const EV_CREATE = 1,
                 EV_STORE = 2;

    ################################### Properties ###################################

    /**
     * Название первичного ключа модели объекта.
     *
     * @var    string
     */
    /*__abstract__*/ protected string $_primaryName;

    /**
     * Массив зарегистрированных полей модели объекта
     * (соответствие свойств объекта к полям в БД).
     *
     * @var    array [string =>[dbName => string OPTIONAL, // название поля таблицы для выбора свойства из БД
     *                          dbSelect => string OPTIONAL, // sql выражение для выбора свойства из БД
     *                          type => int {VarService::T_...}), // тип значения свойства
     *                          tag => int {AbstractModel::TAG_...} OPTIONAL, // тип свойства
     *                          null => bool OPTIONAL, // может ли свойство принимать NULL значение [по умолчанию: false]
     *                          emptyToNull => bool OPTIONAL, // можно ли пустое значение свойства преобразовывать в NULL значение [активно при null = true, по умолчанию: true]
     *                          readonly => bool OPTIONAL, // признак, что свойство только для чтения [по умолчанию: false]
     *                          enum => [string, ...] OPTIONAL] // массив возможных значений для типов VarService::T_ENUM и VarService::T_ESET
     */
    /*__abstract__*/ protected array $_fields;

    /**
     * Свойства объекта, которые необходимо загрузить перед различными
     * операциями модельного объекта.
     *
     * @var    array [load => [string, ...], store => [string, ...], remove => [string, ...]]
     */
    protected array $_preloadFields = ['load' => [], 'store' => [], 'remove' => []];

    /**
     * Был ли загружен модельный объект (из БД).
     *
     * @var    bool
     */
    protected bool $_isLoaded = false;

    /**
     * Идентификатор модельного объекта.
     *
     * @var    int
     */
    protected int $_objectId;

    /**
     * Загруженные свойства (поля) модели объекта.
     *
     * @var    array [string => mixed, ...]
     */
    protected array $_props = [];

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _getSubscribedServices(): array
    {
        return array
        (
            'global.connection' => true, // :TODO: или ввести dbName как отдельный параметр или в 'global.connection' добавить свойство dbName
            //'event.changedata.before' => array
            //(
            //    'source' => '',
            //    'instanceof' => 'AbstractModelView',
            //    // 'extraParams' => null
            //),
            //'event.changedata.after' => array
            //(
            //    'source' => '',
            //    'instanceof' => null,
            //    // 'extraParams' => null
            //),
        );
    }

    /**
     * Конструктор класса.
     *
     * @param      int $objectId OPTIONAL,
     * @param      array $props [string => mixed, ...] OPTIONAL
     * @throws     DbException
     */
    public function __construct(int $objectId = 0, array $props = [])
    {
        assert($objectId >= 0);

        // объект считается загруженным, если переданы некоторые свойства этого объекта
        // (т.е. объект был уже загружен ранее в другом месте или другим способом)
        if ($this->_setProps($props) && $objectId > 0)
        {
            $this->_isLoaded = true;
        }

        $this->_objectId = $objectId;
        $this->_props[$this->_primaryName] = $objectId;

        $this->_init();
    }

    /**
     * Добавление массива свойств модельному объекту.
     *
     * @param      array  $props [string => mixed, ...]
     * @return     bool
     * @throws     DbException :WARNING: never throws
     */
    private function _setProps(array $props): bool
    {
        $isSet = false;

        foreach ($props as $name => $value)
        {
            if (!isset($this->_fields[$name]))
            {
                trigger_error(sprintf('The added property %s is not registered in the model object, so it will be skipped', $name), E_USER_NOTICE);
                continue;
            }

            if (($value instanceof HelperExpr) ||
                    (isset($this->_fields[$name]['tag']) && self::TAG_HELPER === $this->_fields[$name]['tag']))
            {
                continue;
            }

            $this->_props[$name] = (!empty($this->_fields[$name]['null']) && null === $value) ?
                                    null :
                                    $this->_props[$name] = VarService::cast($this->_fields[$name]['type'], $value);

            $isSet = true;
        }

        return $isSet;
    }

    /**
     * Инициализация модельного объекта, вызывается во время работы конструктора.
     */
    protected function _init(): void {}

    /**
     * Загрузка нового объекта из БД в объектную модель.
     *
     * @param      int  $objectId
     * @param      array|null  $fields OPTIONAL [string, ...]
     * @param      array  $props OPTIONAL [string => mixed, ...]
     * @return     bool
     * @throws     DbException
     */
    public function load(int $objectId, array $fields = null, array $props = []): bool
    {
        if ($objectId <= 0)
        {
            return false;
        }

        // разрыв ссылок созданных для предыдущего модельного объекта
        $this->_unlinkAllServices();

        $this->_props = [];

        // объект считается загруженным, если переданы некоторые свойства этого объекта
        $this->_isLoaded = $this->_setProps($props);

        $this->_objectId = $objectId;
        $this->_props[$this->_primaryName] = $objectId;

        if (null === $fields)
        {
            $fields = $this->_preloadFields['load'];
        }

        if (!empty($fields) && $this->_preload($fields))
        {
            $this->_isLoaded = true;
        }

        return $this->_isLoaded;
    }

    /**
     * Очистка модельного объекта (альтернатива создания нового объекта).
     *
     * @return      AbstractModel
     */
    public function &reset(): AbstractModel
    {
        // разрыв ссылок созданных для предыдущего модельного объекта
        $this->_unlinkAllServices();

        $this->_objectId = 0;
        $this->_props = [$this->_primaryName => 0];
        $this->_isLoaded = false;

        return $this;
    }

    ///**
    // * Проверяется, зарегистрировано ли у модельного объекта указанное свойство.
    // *
    // * @param      string  $name
    // * @return     bool
    // */
    //final public function isRegistered(string $name)
    //{
    //    return isset($this->_fields[$name]);
    //}

    /**
     * Была ли загружена модель объекта.
     *
     * @return     bool
     */
    final public function isLoaded(): bool
    {
        return $this->_isLoaded;
    }

    /**
     * Возвращение названия первичного ключа.
     *
     * @param      bool   $nameForDb OPTIONAL
     * @param      bool   $removePrefix OPTIONAL
     * @return     string
     */
    final public function getPrimaryName(bool $nameForDb = true, bool $removePrefix = true): string
    {
        if (!$nameForDb)
        {
            return $this->_primaryName;
        }

        $name = $this->_fields[$this->_primaryName]['dbName'];

        if ($removePrefix && false !== ($index = strpos($name, '.')))
        {
            $name = substr($name, $index + 1);
        }

        return $name;
    }

    ///**
    // * Возвращение всех названий зарегистрированных полей модели объекта.
    // *
    // * @return     array [string, ...]
    // */
    //final public function getFields(): array
    //{
    //    return array_keys($this->_fields);
    //}

    /**
     * Возвращение мета-информации об указанном поле модельного объекта.
     *
     * @param      string  $name
     * @return     array|null (см. AbstractModel::$_fields)
     */
    final public function getFieldMeta(string $name): ?array
    {
        return $this->_fields[$name] ?? null;
    }

    /**
     * Возвращение идентификатора модели объекта.
     *
     * @return     int
     */
    final public function getId(): int
    {
        return $this->_objectId;
    }

    /**
     * Возвращение указанного загруженного свойства объектной модели.
     * (Если уверенность, что свойство ранее было загружено, то указывается $fromCache = true)
     *
     * @param      string  $name
     * @param      bool   $fromCache OPTIONAL
     * @return     mixed
     * @throws     DbException
     */
    public function getProperty(string $name, bool $fromCache = false)
    {
        $this->_checkIfNotInitAndThrowException();
        assert(isset($this->_fields[$name]));

        if (!isset($this->_fields[$name]))
        {
            return null;
        }

        if (!$fromCache)
        {
            unset($this->_props[$name]);
        }

        if (array_key_exists($name, $this->_props) ||
                $this->_preload([$name]))
        {
            return $this->_props[$name];
        }

        return null;
    }

    /**
     * Возвращение свойств объектной модели.
     * Если в массиве $fields указать в качестве ключей строки (алиасы),
     * то при возвращении массива ключи сохранятся:
     *     [aliase => fieldName, ...] => [aliase => fieldValue]
     * Если указан пустой массив $fields, то будут
     * возвращены только все уже загруженные свойства объекта.
     *
     * @param      array  $fields OPTIONAL [string, ...] or [string => string, ...]
     * @return     array [string => mixed, ...]
     * @throws     DbException
     */
    public function getProperties(array $fields = []): array
    {
        $this->_checkIfNotInitAndThrowException();

        if (empty($fields))
        {
            return $this->_props;
        }

        if (!$this->_preload($fields))
        {
            return [];
        }

        ##################################################################################

        $result = [];

        foreach ($fields as $alias => $field)
        {
            $result[is_string($alias) ? $alias : $field] = $this->_props[$field];
        }

        return $result;
    }

    /**
     * Создание нового объекта в БД.
     *
     * @param      array  $props [string => mixed, ...]
     * @return     bool
     * @throws     DbException
     */
    public function create(array $props): bool
    {
        $creating = true;
        $props = $this->_prepareProps($props);

        ##################################################################################

        // если имеется обработчик event.changedata.before, то он вызывается
        if (null !== ($handler = &$this->injectService('event.changedata.before', true, false)))
        {
            // выполнение некоторых действий перед добавлением данных объекта
            $eventArgs = array('type' => self::EV_CREATE, 'fields' => &$props, 'messages' => array(), 'success' => true);
            $handler->exec($eventArgs);

            // если в при работе компонента возникли какие-либо проблемы, то данные не сохраняются
            $creating = $eventArgs['success'];

            if (!empty($eventArgs['messages']))
            {
                // :TODO: ??????????????????
                //// MrEvent::add(MrEvent::TYPE_WARNING, 'При добавлении данных модельного объекта: ' . PHP_EOL . implode(PHP_EOL, $eventArgs['messages']));
            }
        }

        ##################################################################################

        if ($creating)
        {
            // если данные после работы обработчика можно добавлять
            $this->_objectId = $this->_create($props);
            $this->_props[$this->_primaryName] = $this->_objectId;

            if ($this->_objectId > 0)
            {
                $this->_setProps($props);

                // если имеется обработчик event.changedata.after, то он вызывается
                if (null !== ($handler = &$this->injectService('event.changedata.after', true, false)))
                {
                    // выполнения некоторых действий после добавления данных объекта
                    $eventArgs = array('type' => self::EV_CREATE, 'fields' => &$props);
                    $handler->exec($eventArgs);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Сохранение свойств модельного объекта в БД.
     *
     * @param      array  $props [string => mixed, ...]
     * @return     bool
     * @throws     DbException
     */
    public function store(array $props): bool
    {
        $this->_checkIfNotInitAndThrowException();

        // если обязательные свойства необходимые для сохранения объекта
        // не удалось загрузить, то сохранение отменяется
        if (!empty($this->_preloadFields['store']) &&
                !$this->_preload($this->_preloadFields['store']))
        {
            return false;
        }

        ##################################################################################

        $props = $this->_prepareProps($props);

        $result = false;
        $storing = true;

        // если имеется обработчик event.changedata.before, то он вызывается
        if (null !== ($handler = &$this->injectService('event.changedata.before', true, false)))
        {
            // выполнения некоторых действий перед сохранением данных объекта
            $eventArgs = array('type' => self::EV_STORE, 'fields' => &$props, 'messages' => array(), 'success' => true);
            $handler->exec($eventArgs);

            // если в при работе компонента возникли какие-либо проблемы, то данные не сохраняются
            $storing = $eventArgs['success'];

            if (!empty($eventArgs['messages']))
            {
                // :TODO: ??????????????????
                ////      MrEvent::add(MrEvent::TYPE_WARNING, 'При сохранении данных модельного объекта: ' . PHP_EOL . implode(PHP_EOL, $eventArgs['messages']));
            }
        }

        ##################################################################################

        // если данные после работы обработчика можно сохранять
        if ($storing && ($result = $this->_store($props)))
        {
            $this->_setProps($props);

            // если имеется обработчик event.changedata.after, то он вызывается
            if (null !== ($handler = &$this->injectService('event.changedata.after', true, false)))
            {
                // выполнение некоторых действий после сохранения данных объекта
                $eventArgs = array('type' => self::EV_STORE, 'fields' => &$props);
                $handler->exec($eventArgs);
            }
        }

        return $result;
    }

    /**
     * Удаление из БД информации об объекте и ссылающихся на него ссылок.
     *
     * @param      bool  $forceRemove OPTIONAL
     * @return     bool
     * @throws     DbException
     */
    public function remove(bool $forceRemove = false): bool
    {
        $this->_checkIfNotInitAndThrowException();

        // если обязательные свойства необходимые для сохранения объекта
        // отсутствуют или их все удалось загрузить, то происходит удаление объекта
        if (empty($this->_preloadFields['remove']) ||
                $this->_preload($this->_preloadFields['remove']))
        {
            return $this->_remove($forceRemove);
        }

        return false;
    }

    ##################################################################################
    ##################################################################################
    ##################################################################################

    /**
     * Деструктор класса.
     * Освобождение всех ресурсов используемые объектом.
     */
    public function __destruct()
    {// var_dump('call __destruct() of class: ' . get_class($this));
        unset($this->_injectedServices);

        // /*__not_required__*/ parent::__destruct(); /*__WARNING_not_to_remove__*/
    }

    /**
     * Попытка составить поле на основе уже загруженной информации,
     * и если попытка была неудачной, то возвращается составное поле для sql запроса.
     *
     * @param      string  $fieldName
     * @return     string
     */
    protected function _buildFieldName(string $fieldName): string { return ''; }

    /**
     * Загрузка информации о составном поле.
     * Если по каким-то причинам поле не было загружено, то
     * следует выбрасить исключение типа DbException.
     *
     * @param      string  $fieldName
     */
    protected function _loadField(string $fieldName): void { }

    /**
     * Предварительная загрузка указанных полей из БД в объектную модель.
     *
     * @param      array  $fields [string, ...]
     * @return     bool
     * @throws     DbException
     */
    protected function _preload(array $fields): bool
    {
        $result = false; // загрузилось ли хотя бы одно свойство объекта

        $selectNames = '';
        $loadFields = [];

        foreach ($fields as $field)
        {
            assert(isset($this->_fields[$field]));

            if (!isset($this->_fields[$field]))
            {
                continue;
            }

            // если свойство уже было загружено ранее
            if (array_key_exists($field, $this->_props))
            {
                $result = true;
                continue;
            }

            if (!isset($this->_fields[$field]['tag']))
            {
                // если у свойства имеется соответствие c названием поля в БД
                if (!empty($this->_fields[$field]['dbSelect']))
                {
                    $selectNames .= ',' . $this->_fields[$field]['dbSelect'] . ' as ' . $field;
                }
                else // if (!empty($this->_fields[$field]['dbName']))
                {
                    $selectNames .= ',' . $this->_fields[$field]['dbName'] . ' as ' . $field;
                }
            }
            // если это вычисляемое свойство, то оно загружается после загрузки основных свойств модели
            else if (self::TAG_CALCULATED === $this->_fields[$field]['tag'])
            {
                $loadFields[] = $field;
            }
            else if (self::TAG_COMPLEX === $this->_fields[$field]['tag'])
            {
                if ($selectName = $this->_buildFieldName($field))
                {
                    $selectNames .= ',' . $selectName . ' as ' . $field;
                }
            }
            // иначе это вспомагательное TAG_HELPER свойство, которое отсутствует в БД
        }

        ##################################################################################

        // выборка всех свойств модели объекта, которые не были найдены в кэше $this->_props
        if ('' !== $selectNames)
        {
            $selectNames = substr($selectNames, 1); // remove ","

            $sql = sprintf("SELECT %s
                            FROM %s
                            WHERE %s = %u
                            LIMIT 0, 1",
                            $selectNames, $this->_getFrom($selectNames), $this->_fields[$this->_primaryName]['dbName'], $this->_objectId);

            ##################################################################################

            /* @var Adapter */ $connDb = &$this->injectService('global.connection')->db();

            if ($row = $connDb->fetchRow($sql))
            {
                if (!$this->_setProps($row))
                {
                    require_once 'mrcore/exceptions/DbException.php';
                    throw new DbException(sprintf('An empty Model %s is loaded. sql: %s', get_class($this), $sql));
                }

                $result = true;
            }
            else
            {
                require_once 'mrcore/exceptions/DbException.php';
                throw new DbException(sprintf('Model %s not loaded. sql: %s', get_class($this), $sql));
            }
        }

        ##################################################################################

        // загрузка вычисляемых свойств
        foreach ($loadFields as $field)
        {
            // если поле не будет загружено,
            // то выбрасывается исключение DbException
            $this->_loadField($field);
            $result = true;
        }

        return $result;
    }

    /**
     * Предварительная подготовка свойств объекта перед его добавлением/сохранением в БД:
     * - приведение к соответствующему формату,
     * - отбрасывание свойств только для чтения и т.д.
     * (подготовленные поля передаются в методы _create(), _store().
     *
     * @param      array  $props [string, ...]
     * @return     array [string, ...]
     * @throws     DbException :WARNING: never throws
     */
    protected function _prepareProps(array $props): array
    {
        $result = [];

        foreach ($props as $name => &$value)
        {
            // допускаются свойства типа TAG_HELPER без признака 'readonly' = true
            if (!isset($this->_fields[$name]) || !empty($this->_fields[$name]['readonly']) ||
                (isset($this->_fields[$name]['tag']) && self::TAG_HELPER !== $this->_fields[$name]['tag']))
            {
                continue;
            }

            if ($value instanceof HelperExpr)
            {
                $result[$name] = $value;
                continue;
            }

            $result[$name] = VarService::cast($this->_fields[$name]['type'], $value);
        }

        return $result;
    }

    /**
     * Возвращается объект для корректной установки значений полей
     * перед их сохранением в БД.
     *
     * @param      array  $props [string, ...]
     * @return     HelperFieldSet
     */
    final protected function &_createHelperFieldSet(array $props): HelperFieldSet
    {
        require_once 'mrcore/models/HelperFieldSet.php';

        $result = new HelperFieldSet($this->_fields, $props);

        return $result;
    }

    /**
     * Описание таблиц, которые участвуют в запросе для выборки заданных полей модели объекта.
     *
     * @param      string  $selectNames
     * @return     string
     */
    abstract protected function _getFrom(string $selectNames): string;

    /**
     * Создание нового модельного объекта.
     *
     * @param      array  $props [string, ...]
     * @return     int
     */
    abstract protected function _create(array &$props): int;

    /**
     * Сохранение информации о модельном объекте.
     *
     * @param      array  $props [string, ...]
     * @return     bool
     */
    abstract protected function _store(array &$props): bool;

    /**
     * Удаление из БД информации об объекте и ссылающихся на него ссылок.
     *
     * @param      bool  $forceRemove OPTIONAL
     * @return     bool
     */
    abstract protected function _remove(bool $forceRemove = false): bool;

    /**
     * Генерация исключения, если модельный объект не был инициализирован.
     *
     * @throws     DbException
     */
    final protected function _checkIfNotInitAndThrowException(): void
    {
        if ($this->_objectId <= 0)
        {
            require_once 'mrcore/exceptions/DbException.php';
            throw new DbException(sprintf('The model %s must be loaded first', get_class($this)));
        }
    }

}