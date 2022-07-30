<?php declare(strict_types=1);
namespace mrcore\web;

/**
 * Узел типа Action.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ACTION_CONTEXT
 *
 * @template  T_MIDDLEWARE
 *
 * @template  T_NODEACTION_PROPS=array{?class: string, ?middleware: T_MIDDLEWARE, ?context: T_ACTION_CONTEXT}
 */
class NodeAction
{
    /**
     * @var  T_MIDDLEWARE
     */
    public array $middleware = [];

    /**
     * @var  T_ACTION_CONTEXT
     */
    public array $context = [];

    #################################### Methods #####################################

    /**
     * @param T_NODEACTION_PROPS $props
     */
    public static function factory(array $props): self
    {
        $params = [];

        foreach (['class', 'middleware', 'context'] as $name)
        {
            $params[] = $props[$name] ?? null;
        }

        return new self(...$params);
    }

    public function __construct(public string $class,
                                array $middleware = null,
                                array $context = null)
    {
        if (null !== $middleware)
        {
            $this->middleware = $middleware;
        }

        if (null !== $context)
        {
            $this->context = $context;
        }
    }

}