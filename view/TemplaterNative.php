<?php
namespace mrcore\view;

require_once 'mrcore/view/InterfaceTemplater.php';

// used var: $_ENV['MRCORE_DEBUG']

/**
 * Реализация интерфейса для подключения нативного (php) шаблонизатора к системе mrcore.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/view
 */
class TemplaterNative implements InterfaceTemplater
{
    /**
     * Путь к корневому шаблону, который будет использовать
     * шаблонизатор для формирования конечной страницы.
     *
     * @var    string
     */
    private string $_templatePath = '';

    /**
     * Массив данных, которые шаблонизатор передаст в указанный выше шаблон,
     * для формирования конечной страницы.
     *
     * @var    array
     */
    private array $_data = [];

    #################################### Methods #####################################

    ##################################################################################
    # InterfaceTemplater Members

    /**
     * Возвращение пути к корневому шаблону шаблонизатора.
     *
     * @return     string
     */
    public function getTemplate(): string
    {
        return $this->_templatePath;
    }

    /**
     * Установка пути к корневому шаблону шаблонизатора.
     *
     * @param      string  $templatePath
     * @return     InterfaceTemplater
     */
    public function &setTemplate(string $templatePath): InterfaceTemplater
    {
        $this->_templatePath = $templatePath;

        return $this;
    }

    /**
     * Возвращение данных добавленных в шаблонизатор.
     *
     * @return     array
     */
    public function &getData(): array
    {
        return $this->_data;
    }

    /**
     * Установка значения переменной для передачи её в шаблон.
     *
     * @param      string  $varName
     * @param      mixed  $value
     * @return     InterfaceTemplater
     */
    public function &assign(string $varName, $value): InterfaceTemplater
    {
        $this->_data[$varName] = $value;

        return $this;
    }

    /**
     * Установка массива значений переменных для передачи их в шаблон.
     *
     * @param      array  $vars
     * @return     InterfaceTemplater
     */
    public function &assignArray(array $vars): InterfaceTemplater
    {
        foreach ($vars as $varName => $data)
        {
            $this->_data[$varName] = $data;
        }

        return $this;
    }

    /**
     * Формирование данных (html, xml кода) и возвращение его в виде строки.
     *
     * @return     string
     */
    public function fetch(): string
    {
        if ($_ENV['MRCORE_DEBUG'] && !empty($_REQUEST['_DBG_DATA']))
        {
            ob_start();
            var_dump($this->_data);
            $result = ob_get_clean();
        }
        else
        {
            // :WARNING: переменная $_vars будет доступна в подключённом шаблоне
            $_vars = &$this->_data;

            ob_start();
            require($this->_templatePath);
            $result = ob_get_clean();
        }

        return $result;
    }

    /**
     * Формирование данных (html, xml кода) и передача их клиенту.
     */
    public function display(): void
    {
        if ($_ENV['MRCORE_DEBUG'] && !empty($_REQUEST['_DBG_DATA']))
        {
            var_dump($this->_data);
        }
        else
        {
            // переменная $_vars будет доступна в подключённом шаблоне
            $_vars = &$this->_data;
            require($this->_templatePath);
        }
    }

    # End InterfaceTemplater Members
    ##################################################################################

}