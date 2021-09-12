<?php
namespace mrcore\view;
use DOMDocument;
use Exception;
use XSLTProcessor;
use mrcore\lib\Xml;

require_once 'mrcore/lib/Xml.php';
require_once 'mrcore/view/InterfaceTemplater.php';

// used var: $_ENV['MRCORE_DEBUG']

/**
 * Реализация интерфейса для подключения xslt шаблонизатора к системе mrcore.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/view
 */
class TemplaterXslt implements InterfaceTemplater
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
    private array $_data = array();

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
     * @throws     Exception
     */
    public function fetch(): string
    {
        $xml = $this->_data2xml();

        if ($_ENV['MRCORE_DEBUG'] && !empty($_REQUEST['_DBG_DATA']))
        {
            return $xml;
        }

        ##################################################################################

        $doc = new DOMDocument();
        $xslt = new XSLTProcessor();

        if (!$doc->load($this->_templatePath))
        {
            throw new Exception(sprintf('Template %s is not found or incorrect', $this->_templatePath));
        }

        if (!$xslt->importStyleSheet($doc))
        {
            throw new Exception(sprintf('StyleSheet of template %s is or incorrect', $this->_templatePath));
        }

        // var_dump('$this->_templatePath :: ' . $this->_templatePath);

        $doc->loadXML($xml);

        return (string)$xslt->transformToXML($doc);
    }

    /**
     * Формирование данных (html, xml кода) и передача их клиенту.
     *
     * @throws Exception
     */
    public function display(): void
    {
        if ($_ENV['MRCORE_DEBUG'] && !empty($_REQUEST['_DBG_DATA']))
        {
            header('Content-Type: application/xml; charset=utf-8');

            // раскодировка для вложенных блоков XML
            echo str_replace('&', '&amp;', htmlspecialchars_decode($this->fetch()));
        }
        else
        {
            echo $this->fetch();
        }
    }

    # End InterfaceTemplater Members
    ##################################################################################

    /**
     * @see    Xml::array2xml()
     */
    /*__private__*/protected function _data2xml(): string
    {
        return Xml::array2xml('data', $this->_data);
    }

}