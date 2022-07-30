<?php declare(strict_types=1);
namespace mrcore\web;

/**
 * Древовидная структура узлов T_ABSTRACT_NODE в качестве которой могу выступать экшены и категории.
 * В качестве ключа каждого узла является его имя и часть пути URL,
 * по которому можно к нему обратиться из вне.
 *
 * - категорией называется узел дерева обязательно содержащий массив дочерних узлов - array<string, T_ABSTRACT_NODE>.
 *   У категории может быть задан параметр defaultNode с помощью которого
 *   передаётся управления к её дочернему узлу по умолчанию.
 *
 * - экшеном называется узел дерева, который можно преобразовать в NodeAction.
 *   У экшена имеются все свойства категории за исключением свойства defaultNode;
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ABSTRACT_NODE=array|string
 *
 * @template  T_MIDDLEWARE
 *
 * @template  T_ACTION_CONTEXT
 */
class NodeTree implements NodesInterface
{
    /**
     * Узел по умолчанию в корне дерева $nodes.
     */
    private string $defaultNode = '';

    /**
     * @var  T_MIDDLEWARE
     */
    private array $rootMiddleware = [];

    #################################### Methods #####################################

    /**
     * @param  array<string, T_ABSTRACT_NODE>  $nodes
     * @param  NodeAction|null  $routerNode // системный узел перенаправления запросов,
     *                                      // вызывается только если не найден узел первого уровня
     * @param  T_MIDDLEWARE|null $rootMiddleware
     */
    public function __construct(private NodeTreeItemInterface $item,
                                private array $nodes,
                                private NodeAction|null $routerNode = null,
                                string $defaultNode = null,
                                array $rootMiddleware = null)
    {
        if (null !== $defaultNode)
        {
            $this->defaultNode = $defaultNode;
        }

        if (null !== $rootMiddleware)
        {
            $this->rootMiddleware = $rootMiddleware;

            if (null !== $routerNode)
            {
                $routerNode->middleware = array_replace($rootMiddleware, $routerNode->middleware);
            }
        }

        assert('' === $this->defaultNode || isset($nodes[$this->defaultNode]));
    }

    /**
     * @inheritdoc
     */
    public function findAction(array &$pathToAction, array &$rewritePath, array &$rewriteFullPath): NodeAction|null
    {
        $nodes = $this->nodes;
        $defaultNode = $this->defaultNode;
        $mergedMiddleware = $this->rootMiddleware;
        $foundNode = null; // свойства текущего узла
        $isRoot = true;

        while (isset($pathToAction[0]))
        {
            // если узел не был найден
            if (!isset($nodes[$pathToAction[0]]))
            {
                // если в первом уровне дерева не нашлось подходящего узла
                if ($isRoot)
                {
                    return $this->routerNode;
                }

                $defaultNode = null;
                break;
            }

            // если явно указан в адресе узел по умолчанию
            if ($defaultNode === $pathToAction[0])
            {
                return null;
            }

            $isRoot = false;
            $currentRewrite = array_shift($pathToAction);
            $rewritePath[] = $currentRewrite;
            $rewriteFullPath[] = $currentRewrite;

            $foundNode = $this->item->parse($nodes[$currentRewrite]);
            $this->_mergeMiddleware($mergedMiddleware, $this->item->getMiddleware());
            $defaultNode = $this->item->getDefaultNode();
            assert(null !== $foundNode || null === $defaultNode);

            if (!$this->item->next($nodes))
            {
                break;
            }
        }

        ##################################################################################

        // если задан узел по умолчанию, то происходит каскадная обработка
        // по дереву $nodes всех вложенных узлов про умолчанию
        if (null !== $defaultNode)
        {
            do
            {
                $foundNode = $this->item->parse($nodes[$defaultNode]);
                $this->_mergeMiddleware($mergedMiddleware, $this->item->getMiddleware());

                if (!$this->item->next($nodes))
                {
                    break;
                }

                $defaultNode = $this->item->getDefaultNode();
            }
            while (true);
        }

        ##################################################################################

        if (null !== $foundNode) // если найден экшен, а не категория
        {
            $this->_mergeMiddleware($foundNode->middleware, $mergedMiddleware);

            return $foundNode;
        }

        return null;
    }

    /**
     * Слияние вспомогательных экшенов указанного узла и его родительских категорий.
     *
     * @param  T_MIDDLEWARE $middleware
     * @param  T_MIDDLEWARE $middleware2
     */
    protected function _mergeMiddleware(array &$middleware, array $middleware2): void
    {
        if (!empty($middleware2))
        {
            $middleware = array_replace($middleware, $middleware2);
        }
    }

}