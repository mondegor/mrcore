<?php
namespace mrcore\view;

/**
 * InterfaceTemplater предназначен для
 * стыковки постороннего шаблонизатора с данной системой.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/view
 */
interface InterfaceTemplater
{

    ##################################################################################
    # InterfaceTemplater Members

    /**
     * Возвращение пути к корневому шаблону шаблонизатора.
     *
     * @return     string
     */
    public function getTemplate(): string;

    /**
     * Установка пути к корневому шаблону шаблонизатора.
     *
     * @param      string  $templatePath
     * @return     InterfaceTemplater
     */
    public function &setTemplate(string $templatePath): InterfaceTemplater;

    /**
     * Возвращение данных добавленных в шаблонизатор.
     *
     * @return     array
     */
    public function &getData(): array;

    /**
     * Установка значения переменной для передачи её в шаблон.
     *
     * @param      string  $varName
     * @param      mixed  $value
     * @return     InterfaceTemplater
     */
    public function &assign(string $varName, $value): InterfaceTemplater;

    /**
     * Установка массива значений переменных для передачи их в шаблон.
     *
     * @param      array  $vars
     * @return     InterfaceTemplater
     */
    public function &assignArray(array $vars): InterfaceTemplater;

    /**
     * Формирование данных (html, xml кода) и возвращение его в виде строки.
     *
     * @return     string
     */
    public function fetch(): string;

    /**
     * Формирование данных (html, xml кода) и передача их клиенту.
     */
    public function display(): void;

    # End InterfaceTemplater Members
    ##################################################################################

}