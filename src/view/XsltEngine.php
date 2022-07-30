<?php declare(strict_types=1);
namespace mrcore\view;
use DOMDocument;
use mrcore\exceptions\ViewException;
use mrcore\lib\Xml;
use XSLTProcessor;

/**
 * Реализация XSLT шаблонизатора.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ARRAY_ITEM
 * @template  T_PROPERTIES
 */
class XsltEngine extends AbstractViewEngine
{
    /**
     * @inheritdoc
     */
    public function render(string $templateName, array $variables = null): string
    {
        $xml = $this->_vars2xml($variables);

        $templatePath = $this->manager->getTemplatePath($templateName);

        if ($this->manager->showRawData())
        {
            return 'templatePath: ' . $templatePath . "\n" .
                   str_replace('&', '&amp;', htmlspecialchars_decode($xml));
        }

        ##################################################################################

        $doc = new DOMDocument();
        $xslt = new XSLTProcessor();

        if (!$doc->load($templatePath))
        {
            throw ViewException::templateIsNotFound($templatePath);
        }

        if (!$xslt->importStyleSheet($doc))
        {
            throw ViewException::templateStyleSheetIsIncorrect($templatePath);
        }

        $doc->loadXML($xml);

        return (string)$xslt->transformToXML($doc);
    }

    ##################################################################################

    /**
     * @param  T_PROPERTIES  $variables
     */
    protected function _vars2xml(array $variables = null): string
    {
        if (null === $variables)
        {
            $variables = [];
        }

        return Xml::array2xml('data', $variables);
    }

}