<?php declare(strict_types=1);
namespace mrcore\view;

/**
 * Интерфейс менеджера шаблонизаторов.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ViewEngine
 */
interface ViewManagerInterface
{
    /**
     * Возвращается указанный шаблонизатор.
     *
     * @param  class-string<T_ViewEngine>  $class
     * @return T_ViewEngine
     */
    public function getViewEngine(string $class): AbstractViewEngine;

    /**
     * Возвращается полный путь к местоположению шаблона.
     */
    public function getTemplatePath(string $templateName): string;

    /**
     *  Отображение RAW данных до рендиренга их шаблонизатором.
     */
    public function showRawData(): bool;

}