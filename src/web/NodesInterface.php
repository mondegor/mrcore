<?php declare(strict_types=1);
namespace mrcore\web;

/**
 * Интерфейс структуры узлов.
 *
 * @author  Andrey J. Nazarov
 */
interface NodesInterface
{
    /**
     * Поиск узла по указанному пути $pathToAction.
     * После поиска в $pathToAction останется не разобранная часть,
     * а разобранная перейдёт в $rewritePath и $rewriteFullPath.
     *
     * @param      string[]  $pathToAction
     * @param      string[]  $rewritePath
     * @param      string[]  $rewriteFullPath
     */
    public function findAction(array &$pathToAction, array &$rewritePath, array &$rewriteFullPath): NodeAction|null;

}