<?php
namespace mrcore\lib;
use DOMDocument;
use DOMElement;
use XMLReader;

/**
 * Библиотека объединяющая методы преобразования XML данных.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/lib
 */
/*__class_static__*/ class Xml
{
    /**
     * Текстовые boolean значения.
     */
    public const BOOL_FALSE = 'false',
                 BOOL_TRUE  = 'true';

    #################################### Methods #####################################

    /**
     * Преобразование массива к строке в виде XML.
     *
     * Первый символ в ключе:
     * # - только если значение является массивом: ключи будут атрибутами с именем id тегов item, значения будут их значениями;
     * ~ - значение не эскейпится;
     * - - значение вставляется как есть (без вызова static::prepareValue());
     * @ - значение будет присвоено атрибуту текущего тега имеющего имя ключа;
     *
     * Если ключу дать имя *, то его значение будет втавлена без тега,
     * т.е. значение станит содержанием родительского тэга.
     *
     * @param      string  $tag
     * @param      array  $array [string => string|int|bool|[string => string|int|bool|, ...], ...]
     * @param      bool  $lower OPTIONAL
     * @param      string  $spaces OPTIONAL
     * @param      bool  $keyAsId OPTIONAL
     * @return     string
     */
    public static function array2xml(string $tag, array $array, bool $lower = false, string $spaces = '', bool $keyAsId = false): string
    {
        $result = $spaces . '<' . $tag;
        $body = '';
        $foundItem = false;
        $directInsert = false;

        foreach ($array as $key => &$value)
        {
            $attrs = '';

            if (is_int($key) || $keyAsId)
            {
                $attrs = ' id="' . $key . '"';
                $key = 'item';
            }
            else
            {
                // если значение не нужно эскейпить
                if (0 === strncmp($key, '~', 1))
                {
                    $key = substr($key, 1);
                    $attrs .= ' escape="no"';
                }
                // если значение нужно вставить как есть без преобразований
                else if ('-' === $key[0])
                {
                    $key = substr($key, 1);
                    $directInsert = true;
                }

                if ($lower)
                {
                    $key = /*--mb_*/strtolower($key);
                }
            }

            ##################################################################################

            // если нужно представить значение в виде атрибута тега $tag
            if (0 === strncmp($key, '@', 1))
            {
                $result .= ' ' . substr($key, 1) . '="' . static::prepareValue($value) . '"';
            }
            else
            {
                if ('*' === $key)
                {
                    assert(is_string($value));

                    $body .= static::prepareValue($value);
                }
                else
                {
                    $itemsKeyAsId = false;

                    if (0 === strncmp($key, '#', 1))
                    {
                        $key = substr($key, 1);
                        $itemsKeyAsId = true;
                    }

                    ##################################################################################

                    if ('' === $value)
                    {
                        $body .= $spaces . '    <' . $key . $attrs . '/>' . "\n";
                    }
                    else if (is_array($value))
                    {
                        if (!empty($value))
                        {
                            $body .= static::array2xml($key . $attrs, $value, $lower, $spaces . '    ', $itemsKeyAsId);
                        }
                    }
                    else
                    {
                        $body .= $spaces . '    <' . $key . $attrs . '>' . ($directInsert ? $value : static::prepareValue($value)) . '</' . $key . '>' . "\n";
                    }
                }

                $foundItem = true;
            }
        }

        ##################################################################################

        if ($foundItem)
        {
            // в функцию может быть передан $tag вместе с атрибутами,
            // поэтому они обрезаются
            if (false !== ($index = strpos($tag, ' ')))
            {
                $tag = substr($tag, 0, $index);
            }

            $result .= '>' . "\n" . $body . $spaces . '</' . $tag . '>' . "\n";
        }
        else
        {
            $result .= '/>' . "\n";
        }

        return $result;
    }

    /**
     * Преобразование массива к xml и добавление его к DOMDocument.
     *
     * @param      DOMDocument  $xml
     * @param      DOMElement  $element
     * @param      array  $array [string => string|int|bool|self::array, ...]
     * @param      bool  $asTags OPTIONAL
     */
    public static function addItemsToDOM(DOMDocument $xml, DOMElement $element, array $array, bool $asTags = false): void
    {
        foreach ($array as $key => &$value)
        {
            if (is_array($value))
            {
                if (!empty($value))
                {
                    if (is_int($key))
                    {
                        $_element = $xml->createElement('item');
                        $_element->setAttribute('id', $key);
                    }
                    else
                    {
                        $_element = $xml->createElement($key);
                    }

                    static::addItemsToDOM($xml, $_element, $value, $asTags);
                    $element->appendChild($_element);
                    unset($_element);
                }
            }
            else if ('' !== (string)$value)
            {
                if ($asTags)
                {
                    if (is_int($key))
                    {
                        $_element = $xml->createElement('item', $value);
                        $_element->setAttribute('id', $key);
                    }
                    else
                    {
                        $_element = $xml->createElement($key, $value);
                    }

                    $element->appendChild($_element);
                    unset($_element);
                }
                else
                {
                    if (is_int($key))
                    {
                        $_element = $xml->createElement('item', $value);
                        $_element->setAttribute('id', $key);
                        $element->appendChild($_element);
                        unset($_element);
                    }
                    else
                    {
                        $element->setAttribute($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Преобразование строки валидного XML к ассоциативному PHP массиву.
     *
     * :WARNING: При $simple = true данный метод применяется для xml,
     *           у которых все соседние тэги (имеющие общего родителя)
     *           должны быть уникальными (иначе в массиве останится последний их одинаковых).
     *           Также в этом режиме могут быть потеряны атрибуты тегов.
     *
     * @param      string  $xml
     * @param      bool  $simple OPTIONAL
     * @return     array
     */
    public static function xml2array(string $xml, bool $simple = false): array
    {
        // aa - вспомагательный обрамляющий тэг, который исключается при преобразовании

        // if ('' === $xml)
        // {
        //     return [];
        // }

        if ($simple)
        {
            return json_decode(json_encode((array)simplexml_load_string('<aa>' . $xml . '</aa>', null, LIBXML_NOCDATA), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        ##################################################################################

        $oXml = new XMLReader();
        $oXml->XML('<aa>' . $xml . '</aa>');

        $result = [];
        static::_parseXml($oXml, $result);

        // если указана сторока (не XML), то она оборачивается в тег <p>
        return $result[0]['t'] ?? [['n' => 'p', 'v' => $result[0]['v']]]; // remove tag aa
    }

    /**
     * Приведение указанного значения к типу bool XML.
     *
     * @param      bool  $value
     * @return     string
     */
    public static function castBool(bool $value): string
    {
        return $value ? self::BOOL_TRUE : self::BOOL_FALSE;
    }

    /**
     * Подготовка значения при вставки в XML документ.
     *
     * @param      string|int|float|bool  $value
     * @return     string
     */
    public static function prepareValue($value): string
    {
        if (is_string($value))
        {
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_DISALLOWED);
        }
        else if (is_bool($value))
        {
            $value = static::castBool($value);
        }

        return (string)$value;
    }

    /**
     * XML Parser
     *
     * @param      XMLReader  $xml
     * @param      array  $elements [string|[n => string, a => [[string => string], ...], ...], ...]
     */
    /*__private__*/protected static function _parseXml(XMLReader $xml, array &$elements): void
    {
        while ($xml->read())
        {
            switch ($xml->nodeType)
            {
                case XMLReader::END_ELEMENT:
                    return; // :WARNING:

                case XMLReader::ELEMENT:
                    $element = ['n' => strtolower($xml->name)]; // n - name
                    $isEmptyElement = $xml->isEmptyElement;

                    if ($xml->hasAttributes)
                    {
                        while ($xml->moveToNextAttribute())
                        {
                            $element['a'][$xml->name] = $xml->value; // a - attrs
                        }
                    }

                    if (!$isEmptyElement)
                    {
                        $children = [];
                        static::_parseXml($xml, $children);

                        if (!empty($children))
                        {
                            if (is_string($children[0]) && !isset($children[1]))
                            {
                                $element['v'] = array_shift($children); // v - value (text)
                            }
                            else
                            {
                                $element['t'] = $children; // t - tags
                            }
                        }
                    }

                    $elements[] = $element;
                    break;

                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    $elements[] = $xml->value;
                    break;
            }
        }
    }

}