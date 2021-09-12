<?php declare(strict_types=1);
namespace mrcore\base\testdata;
use mrcore\lib\Xml;

require_once 'mrcore/lib/Xml.php';

class ConcreteXml extends Xml
{

    //public function testGetParams(): array
    //{
    //    return $this->_params;
    //}

}

class ConcreteXmlCastBool extends Xml
{

    public static function castBool(bool $value): string
    {
        return Xml::BOOL_TRUE;
    }

}

class ConcreteXmlPrepareValue extends Xml
{

    public static function prepareValue($value): string
    {
        return sprintf('prepared-%s', $value);
    }

}