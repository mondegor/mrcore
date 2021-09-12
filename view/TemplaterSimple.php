<?php
namespace mrcore\view;

require_once 'mrcore/view/InterfaceTemplater.php';

// used var: $_ENV['MRCORE_DEBUG']

/**
 * Реализация интерфейса для подключения простого шаблонизатора к системе mrcore.
 * Шаблонизатор поддерживает подшаблоны, списки с однотипными данными.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/view
 */
class TemplaterSimple implements InterfaceTemplater
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
        $this->_data = array_replace($this->_data, $vars);

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
            $result = $this->_fetch($this->_templatePath, $this->_data);
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
            echo $this->_fetch($this->_templatePath, $this->_data);
        }
    }

    # End InterfaceTemplater Members
    ##################################################################################

    /**
     * Вставка данных в указанный шаблон
     * (поддерживаются подшаблоны).
     *
     * @param      string  $templatePath
     * @param      array  $data
     * @return     string
     */
    private function _fetch(string $templatePath, array $data): string
    {
        if ($result = (string)file_get_contents($templatePath))
        {
            // список условных переменных, которые влияют на части шаблона.
            // В этот список переменная попадает, если перед переменной задать знак $.
            $conditionVars = array();

            $search = array();
            $replace = array();

            foreach ($data as $varName => $value)
            {
                // если это условная переменная
                if ('$' === $varName[0])
                {
                    $conditionVars[$varName] = $value;
                    continue;
                }

                ##################################################################################

                // по умолчанию все переменны экранируются, но если перед переменной задать знак #,
                // то экранирование значения будет пропущено
                $excludeEscape = false;

                if ('#' === $varName[0])
                {
                    $excludeEscape = true;
                    $varName = substr($varName, 1);
                }

                ##################################################################################

                $search[] = '%' . $varName . '%';

                // если значение пустое, то ключ в шаблоне просто стирается
                if (empty($value))
                {
                    $replace[] = '';
                }
                // если указан массив, то он может быть двух видов:
                // - массив с данными (в нём должен содержаться ключ под названием 'templatePath');
                // - массив список;
                else if (is_array($value))
                {
                    // если это массив с данными, то они вставляются в шаблон и формируется строка
                    if (!empty($value['templatePath']))
                    {
                        // unset($value['templateName']);
                        $replace[] = $this->_fetch($value['templatePath'], $value);
                    }
                    // иначе это массив список, в каждом таком массиве
                    // должен находится подмассив с данными
                    else
                    {
                        $string = '';

                        foreach ($value as $subvalue)
                        {
                            if (!empty($subvalue['templatePath']))
                            {
                                // unset($subvalue['templateName']);
                                $string .= $this->_fetch($subvalue['templatePath'], $subvalue);
                            }
                            else
                            {
                                $string .= '#UNKNOWN#';
                            }

                            $string .= "\n";
                        }

                        $replace[] = $string;
                    }
                }
                // иначе происходит простое экранирование строки
                else
                {
                    $replace[] = $excludeEscape ? $value : htmlspecialchars($value);
                }
            }

            ##################################################################################

            if (!empty($conditionVars))
            {
                foreach ($conditionVars as $varName => $value)
                {
                    $condition = 'if:' . $varName;

                    // если условие выполняется, то размечанный кусок кода остаётся в шаблоне,
                    // а разметка стирается
                    if ($value)
                    {
                        $search[] = '{' . $condition . '}';
                        $replace[] = '';

                        $search[] = '{/' . $condition . '}';
                        $replace[] = '';
                    }
                    else
                    {
                        // если условие не выполняется, то размеченные куски кода удаляются
                        while (false !== ($istart = /*ok*/mb_strpos($result, '{' . $condition . '}')) &&
                               false !== ($iend = /*ok*/mb_strpos($result, '{/' . $condition . '}')))
                        {
                            $result = /*ok*/mb_substr($result, 0, $istart) . /*ok*/mb_substr($result, $iend + /*ok*/mb_strlen($condition) + 3);
                        }
                    }
                }
            }

            ##################################################################################

            if (!empty($search))
            {
                $result = str_replace($search, $replace, $result);
            }
        }

        return $result;
    }

}