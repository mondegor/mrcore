<?php declare(strict_types=1);
namespace mrcore\web;
use mrcms\units\ControllerInterface;

/**
 * Абстракция секции - корневая структура, с поддержкой привязки к отдельному домену.
 * В ней описываются узлы (экшены, категории), которые обрабатываются
 * с помощью одного фронт контроллера, который здесь же и определяется.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ACTION_CHAIN_ITEM=array{0: string, 1: T_ACTION_CONTEXT} *
 * @template  T_ABSTRACT_NODE
 * @template  T_MIDDLEWARE=array<string, T_ACTION_CONTEXT[]> // string=AbstractAction::class
 * @template  T_MRAPP_LANGUAGES
 *
 * @template  T_SECTION_OPTIONS=array{sectionName: string, // название секции (устанавливается автоматически)
 *                                    rewriteRoot: string, // путь к секции (устанавливается автоматически)
 *                                    ?sid: string} // название идентификатора куки в которой хранится токен аутентификации
 *
 * @template  T_SECTION_RESPONSE_OPTIONS=array{responseClass: string, // название класса ответа сервера наследуемого от ResponseInterface
 *                                             ?httpVersion: string, // версия http протокола (по умолчанию используется настройка проекта)
 *                                             ?contentType: string, // тип ответа сервера (по умолчанию используется настройка проекта)
 *                                             ?charset: string} // кодировка ответа сервера (по умолчанию используется настройка проекта)
 *
 * @template  T_SECTION_VIEW_OPTIONS=array{viewEngineClass: string, // класс шаблонизатора, с помощью которого будет сформирован ответ сервера
 *                                         themeDir: string} // путь к теме c шаблонами относительно {@see $_ENV['MRAPP_DIR_TEMPLATES']}
 */
abstract class AbstractSection
{
    /**
     * Класс контроллера приложения.
     *
     * @var  string
     */
    /*__abstract__*/ protected string $controllerClass;

    /**
     * Поддерживаемые языки в рамках данной секции.
     *
     * @var  string[]
     */
    protected array $supportedLanguages = [];

    /**
     * Общие настройки.
     *
     * @var  T_SECTION_OPTIONS
     */
    protected array $options = [
        // 'sectionName' => '',
        // 'rewriteRoot' => '',
        // 'sid' => '',
    ];

    /**
     * Настройки используемые при формировании ответа сервера клиенту.
     *
     * @var  T_SECTION_RESPONSE_OPTIONS
     */
    protected array $responseOptions = [
        // 'responseClass' => HttpResponse::class,
        // 'httpVersion' => '',
        // 'contentType' => '',
        // 'charset' => '',
    ];

    /**
     * Настройки используемые при формировании контента.
     *
     * @var  T_SECTION_VIEW_OPTIONS
     */
    protected array $viewOptions = [
        // 'viewEngineClass' => AbstractViewEngine::class,
        // 'themeDir' => 'shared/',
    ];

    /**
     * Набор промежуточных узлов, привязанных к основному узлу.
     * Если они присутствует, то на выходе получается цепочка узлов,
     * каждый узел которой нужно последовательно запустить.
     *
     * @var      T_MIDDLEWARE
     */
    protected array $middleware = [];

    /**
     * Узел по умолчанию в корне дерева $nodes.
     */
    protected string|null $defaultNode = null;

    /**
     * Список узлов текущей секции.
     *
     * @var  array<string, T_ABSTRACT_NODE>
     */
    protected array $nodes = [];

    #################################### Methods #####################################

    public function __construct(string $sectionName, protected ServiceBag $serviceBag)
    {
        $pathToAction = $serviceBag->getPath();
        $serviceBag->getService('web.lang')->init($pathToAction, $this->supportedLanguages);

        $this->options['sectionName'] = $sectionName;
        $this->options['rewriteRoot'] = implode('/', $pathToAction->rewritePath) . '/';

        $this->_init();

        assert(!empty($this->nodes), sprintf('Nodes are not found in %s', __CLASS__));
        assert(null === $this->defaultNode || isset($this->nodes[$this->defaultNode]));

        // если секция содержит один узел и его имя совпадает с названием секции,
        // то он становится узлом по умолчанию
        if (1 === count($this->nodes) && $sectionName === array_key_first($this->nodes))
        {
            $this->defaultNode = $sectionName;
        }
    }

    /**
     * Создание фронт контроллера для текущей секции.
     */
    public function createController(): ControllerInterface
    {
        return new $this->controllerClass
        (
            $this->serviceBag,
            $this->options,
            $this->responseOptions,
            $this->viewOptions,
            $this->_getActionChain()
        );
    }

    /**
     *  Инициализация дополнительных опций в классах наследниках.
     */
    protected function _init(): void { }

    /**
     *  Создание объекта для поиска узла в $this->nodes.
     */
    protected function _createNodeTree(): NodesInterface
    {
        return new NodeTree
        (
            $this->_createNodeTreeItem(),
            $this->nodes,
            $this->_createRouterNode(),
            $this->defaultNode,
            $this->middleware
        );
    }

    /**
     *  Создание объекта для разбора конкретного узла из $this->nodes.
     */
    protected function _createNodeTreeItem(): NodeTreeItemInterface
    {
        return new NodeTreeItem($this->serviceBag->getEnv()->getRequestMethod());
    }

    /**
     *  Создание системного узла перенаправления запросов - роутера.
     */
    protected function _createRouterNode(): NodeAction|null
    {
        return null; // new NodeAction(RouterAction::class);
    }

    /**
     * Поиск узла основного экшена в дереве узлов секции.
     * Если при поиске обнаружены вспомогательные middleware экшены,
     * то они собираются в цепочку, а в конец добавляется основной найденный экшен.
     *
     * @return  T_ACTION_CHAIN_ITEM[]
     */
    protected function _getActionChain(): array
    {
        $pathToAction = $this->serviceBag->getPath();

        $node = $this->_createNodeTree()->findAction
        (
            $pathToAction->residuePath,
            $pathToAction->rewritePath,
            $pathToAction->rewriteFullPath
        );

        $result = [];

        if (null !== $node)
        {
            foreach ($node->middleware as $class => $context)
            {
                $result[] = [$class, $context];
            }

            $result[] = [$node->class, $node->context];
        }

        return $result;
    }

}