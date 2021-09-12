<?php declare(strict_types=1);
namespace mrcore\units;
use mrcore\services\TraitServiceInjection;
use mrcore\models\AbstractModelView;

require_once 'mrcore/services/TraitServiceInjection.php';
require_once 'mrcore/units/AbstractUnit.php';

/**
 * Базовой экшен, которому передаётся управление системой
 * при внешнем запросе к нему.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/units
 */
abstract class AbstractAction extends AbstractUnit
{
    use TraitServiceInjection;

    /**
     * Возможные результаты работы run метода.
     *
     * @conts  int
     */
    public const RESULT_SUCCESS   = 200, // приложение выполняется в штатном режиме;
                 RESULT_FORBIDDEN = 403, // доступ к странице отклонён;
                 RESULT_NOT_FOUND = 404; // запрашиваемая страница не найдена;

    /**
     * Типы страниц-экшенов.
     *
     * @conts  int
     */
    public const TYPE_ACTION     = 'action',     // экшен;
                 TYPE_CONTROLLER = 'controller', // контроллер;
                 TYPE_TEXTPAGE   = 'textpage',   // текстовая страница;
                 TYPE_XMLPAGE    = 'xmlpage',    // XML страница;
                 TYPE_SYSPAGE    = 'syspage',    // системная страница;
                 TYPE_ERRORPAGE  = 'errorpage';  // страница вывода ошибок;

    ################################### Properties ###################################

    /**
     * Успешно разобранный полный путь к экшену (относительно домена и секции сайта);
     *
     * @var    string
     */
    public string $rewriteFullPath;

    /**
     * Путь, покоторому достаточно обратиться, чтобы загрузить данный экшен (относительно домена и секции сайта);
     * Он отличается от $rewriteFullPath тогда, когда экшен вызывается по умолчанию.
     * :WARNING: по умолчанию не формируется
     *
     * @var    string
     */
    public string $rewritePath = '';

    /**
     * Остаток от успешно разобранного пути, который не удалось до конца разобрать фронт контроллеру.
     * :WARNING: данный путь необходимо доразобрать текущим экшеном (в противном случае система выведит 404 ошибку).
     *
     * @var    array [string, ...]
     */
    public array $residuePath;

    /**
     * Данные для ответа сервером клиенту:
     *   NULL - данные отправлять не планируется или они не сформированны;
     *   STRING - данные возвращаются в виде строки;
     *   ARRAY - данные передаются в шаблонизатор, после этого возвращаются в виде строки;
     *
     * @var    string|array|AbstractModelView|null
     */
    public $viewData = null;

    /**
     * Тип экшена.
     *
     * @var    string
     */
    protected string $_actionType = self::TYPE_ACTION;

    /**
     * Контекст экшена (другими словами настройки),
     * которые могут быть переопределены из вне.
     *
     * @var    array
     */
    protected array $_context = [];

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _getSubscribedServices(): array
    {
        return array
        (
            'global.app' => true,
            'global.env' => true,
            'global.response' => true,
            'global.var' => true,
        );
    }

    /**
     * Конструктор класса.
     *
     * @param      string  $unitName
     * @param      array   $parsedPath [path => string, residue => [string, ...]] (/, /path1/path2/)
     * @param      array   $context [string => mixed, ...]
     */
    /*__override__*/ public function __construct(string $unitName, array $parsedPath, array $context)
    {
        assert(preg_match('/^[a-z0-9\-\/]*$/i', $parsedPath['path'], $m) > 0);
        assert(ltrim($parsedPath['path'], '/') === $parsedPath['path']);
        assert(false === strpos($parsedPath['path'], '//'));

        parent::__construct($unitName);

        $this->rewriteFullPath = $parsedPath['path'];
        $this->residuePath = $parsedPath['residue'];

        if (isset($context['actionType']))
        {
            assert(in_array($context['actionType'], [self::TYPE_ACTION, self::TYPE_CONTROLLER,
                                                     self::TYPE_TEXTPAGE, self::TYPE_XMLPAGE,
                                                     self::TYPE_SYSPAGE, self::TYPE_ERRORPAGE], true));

            $this->_actionType = $context['actionType'];
        }

        // если были указаны внутренние настроки,
        // то они всегда переопределяются внешними, если те были заданы
        $this->_context = $context + $this->_context; // :WARNING: слияние массивов (выигрывает первый)

        $this->_init();
    }

    /**
     * Возвращается тип экшена.
     *
     * @return     string
     */
    public function getActionType(): string
    {
        return $this->_actionType;
    }

    ///**
    // * Возвращается контекст (настройки) экшена.
    // *
    // * @param      string  $name OPTIONAL
    // * @return     mixed
    // */
    //public function getContext(string $name = '')
    //{
    //    if ('' === $name)
    //    {
    //        return $this->_context;
    //    }
    //
    //    return $this->_context[$name] ?? null;
    //}

    /**
     * Инициализация объекта экшена, вызывается во время работы конструктора.
     * В массиве $this->_context содержатся данные для экшена:
     * как внутренние настройки, так и переданные из вне.
     *
     * Примеры досрочного завершения работы экшена (метод run вызван не будет):
     * В методе _init() нужно использовать:
     *     \MrApp::$rsp->setRedirect(\mrcore\base\BuilderLink::factory('/'), 302); - будет произведён редирект;
     *     \MrApp::$rsp->setAnswer(404); // 403 - будет вызван экшен - обработчик ошибок;
     */
    protected function _init(): void { }

    /**
     * Непосредственно запуск экшена.
     *
     * @return     int  (RESULT_SUCCESS, RESULT_FORBIDDEN, RESULT_NOT_FOUND)
     */
    abstract public function run(): int;

    /**
     * Деструктор класса.
     * Освобождение всех ресурсов используемые объектом.
     */
    public function __destruct()
    {// var_dump('call __destruct() of class: ' . get_class($this));
        unset($this->_injectedServices);

        // /*__not_required__*/ parent::__destruct(); /*__WARNING_not_to_remove__*/
    }

}