<?php declare(strict_types=1);
namespace mrcore\web;

/**
 * Интерфейс скрывающий структуру узла и
 * позволяющий классу {@see NodeTree} извлекать из него данные.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ABSTRACT_NODE
 *
 * @template  T_ACTION_CONTEXT
 *
 * @template  T_MIDDLEWARE
 */
interface NodeTreeItemInterface
{
    /**
     * Разбор и извлечение данных из указанного узла.
     *
     * @param  T_ABSTRACT_NODE  $node
     */
    public function parse(array|string $node): NodeAction|null;

    /**
     * Возвращается список промежуточных экшенов для последнего обработанного узла.
     *
     * @return  T_MIDDLEWARE
     */
    public function getMiddleware(): array;

    /**
     * Возвращается дочерний узел по умолчанию для последнего обработанного узла.
     */
    public function getDefaultNode(): string|null;

    /**
     * Если имеется список узлов для дальнейшего поиска то вернётся true,
     * и этот список будет положен в $nodes.
     *
     * @param  array<string, T_ABSTRACT_NODE>  $nodes
     */
    public function next(array &$nodes): bool;

}