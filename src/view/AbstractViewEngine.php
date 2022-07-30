<?php declare(strict_types=1);
namespace mrcore\view;

/**
 * Абстракция шаблонизатора предназначенная для генерации текстовых блоков (html, xml)
 * на основе шаблона и переданных ему переменных в виде массива.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ARRAY_ITEM
 * @template  T_PROPERTIES
 */
abstract class AbstractViewEngine
{

    public function __construct(protected ViewManagerInterface $manager) { }

    /**
     * Генерация текстового блока.
     *
     * @param  string  $templateName // название шаблона на основе которого формируется текстовый блок
     * @param  T_PROPERTIES|null  $variables // массив переменных, которые используются в шаблоне
     */
    abstract public function render(string $templateName, array $variables = null): string;

}