<?php declare(strict_types=1);
namespace mrcore\web;

/**
 * Класс извлекает информацию из T_TREE_NODE.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ACTION_CONTEXT
 *
 * @template  T_MIDDLEWARE
 *
 * @template  T_NODE_ACTION=array{class: string, middlewareClasses: T_MIDDLEWARE, context: T_ACTION_CONTEXT}
 *
 * @template  T_TREE_NODE=string|array{?class: string,
 *                                     ?middlewareClasses: T_MIDDLEWARE,
 *                                     ?context: T_ACTION_CONTEXT,
 *                                     ?defaultNode: string,
 *                                     ?nodes: T_TREE_NODE[],
 *                                     ?requestMethod: string,
 *                                     ?GET: string|T_NODE_ACTION,
 *                                     ?POST: string|T_NODE_ACTION,
 *                                     ?PUT: string|T_NODE_ACTION,
 *                                     ?DELETE: string|T_NODE_ACTION}
 */
class NodeTreeItem implements NodeTreeItemInterface
{
    /**
     * Список промежуточных экшенов для последнего обработанного узла.
     *
     * @var  T_MIDDLEWARE
     */
    private array $middleware = [];

    /**
     * Дочерний узел по умолчанию для последнего обработанного узла.
     */
    private string|null $defaultNode = null;

    /**
     * Ссылка на список вложенных узлов последнего обработанного узла.
     *
     * @var  array<string, T_TREE_NODE>  $nodes
     */
    private array $nodes = [];

    #################################### Methods #####################################

    public function __construct(private string $requestMethod)
    {
        assert(in_array($requestMethod, ['GET', 'POST', 'PUT', 'DELETE']));
    }

    /**
     * @inheritdoc
     *
     * @param  T_TREE_NODE  $node
     */
    public function parse(array|string $node): NodeAction|null
    {
        $this->defaultNode = null;
        $this->middleware = [];
        $this->nodes = [];

        if (is_string($node)) // если значение узла является классом экшена
        {
            if ('GET' !== $this->requestMethod)
            {
                trigger_error(sprintf('The current node request method %s is invalid for string format', $this->requestMethod), E_USER_NOTICE);
                return null;
            }

            return $this->_createNodeAction($node);
        }

        if (!empty($node['middlewareClasses']))
        {
            $this->middleware = $node['middlewareClasses'];
        }

        if (isset($node[$this->requestMethod]))
        {
            assert(!isset($node[$this->requestMethod]['defaultNode']));
            assert(!isset($node[$this->requestMethod]['nodes']));

            if (is_string($node[$this->requestMethod]))
            {
                return $this->_createNodeAction($node[$this->requestMethod]);
            }

            return $this->_createNodeActionWithProps($node[$this->requestMethod]);
        }

        if (!empty($node['nodes']))
        {
            $this->nodes = $node['nodes'];
        }

        if (isset($node['class'])) // если узел является экшеном
        {
            assert(!isset($node['defaultNode']));

            $requiredRequestMethod = $node['requestMethod'] ?? 'GET';

            if ($requiredRequestMethod !== $this->requestMethod)
            {
                return null;
            }

            return $this->_createNodeActionWithProps($node);
        }

        assert(!empty($node['nodes']));
        assert(!isset($node['defaultNode']) || isset($node['nodes'][$node['defaultNode']]));

        if (isset($node['defaultNode']))
        {
            $this->defaultNode = $node['defaultNode'];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultNode(): string|null
    {
        return $this->defaultNode;
    }

    /**
     * @inheritdoc
     *
     * @param  array<string, T_TREE_NODE>  $nodes
     */
    public function next(array &$nodes): bool
    {
        if (!empty($this->nodes))
        {
            $nodes = $this->nodes;
            return true;
        }

        return false;
    }

    protected function _createNodeAction(string $class): NodeAction
    {
        return new NodeAction($class);
    }

    protected function _createNodeActionWithProps(array $properties)
    {
        return NodeAction::factory($properties);
    }

}