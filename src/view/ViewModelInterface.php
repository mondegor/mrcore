<?php declare(strict_types=1);
namespace mrcms\view;

/**
 * Интерфейс модели представления.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES
 */
interface ViewModelInterface
{
    /**
     * Возвращается сформированные данные.
     *
     * @return  T_PROPERTIES
     */
    public function getArray(): array;

}