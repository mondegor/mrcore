<?php declare(strict_types=1);
namespace mrcore\view;

/**
 * Шаблонизатор предназначен для генерации текстового блока (html, xml, json)
 * на основе шаблона и переданных ему переменных в виде массива.
 *
 * @author  Andrey J. Nazarov
 */
class RenderView
{
    /**
     * @param  string  templatePath // путь к шаблону на основе которого формируется текстовый блок
     * @param  array  $vars [string => string|&, ...] // массив переменных, которые используются в шаблоне
     */
    public function __construct(protected string $templatePath, protected array $vars) { }

    /**
     * Генерация ответа экшена в виде текстового блока указанным шаблонизатором.
     *
     * @param  array $templaterSettings {@see Section::$options['templater']}
     * @param  array $data  {@see AbstractViewEngine::$vars}
     */
    protected function _renderActionContent(AbstractAction $action, array $templaterSettings, array $data): string
    {
        // если в контексте экшена были переданы переопределяющие параметры,
        // то ими заменяются настройки самого шаблонизатора
        foreach ($templaterSettings as $key => $value)
        {
            if ($action->hasContext($key))
            {
                $templaterSettings[$key] = $action->getContext($key);
            }
        }

        assert(isset($templaterSettings['decorTemplatePath']), 'Key "decorTemplatePath" is not found in $templaterSettings[\'decorTemplatePath\']');

        $templaterSettings['templatePath'] = $templaterSettings['decorTemplatePath'];

        return $this->_renderContent($templaterSettings, $data);
    }

    /**
     * Генерация текстового блока указанным шаблонизатором.
     *
     * @param  array $templaterSettings [class => string, themeDir => string, templatePath => string]
     * @param  array $data  {@see AbstractViewEngine::$vars}
     */
    protected function _renderContent(array $templaterSettings, array $data): string
    {
        assert(isset($templaterSettings['class']), 'Key "class" is not found in $templaterSettings[\'class\']');
        assert(isset($templaterSettings['themeDir']), 'Key "themeDir" is not found in $templaterSettings[\'themeDir\']');
        assert(isset($templaterSettings['templatePath']), 'Key "decorTemplatePath" is not found in $templaterSettings[\'templatePath\']');

        return $this->_createViewEngine
        (
            $templaterSettings['class'],
            $templaterSettings['themeDir'] . ltrim($templaterSettings['templatePath'], '/'),
            $data
        )->render();
    }

    /**
     * @param  array $data {@see AbstractViewEngine::$vars}
     *
     * @template T
     * @param  class-string<T>|null  $class
     * @return T
     */
    protected function _createViewEngine(string $class, string $templatePath, array $data): AbstractViewEngine
    {
        return new $class
        (
            $this->templatesDir . $templatePath,
            $data
        );


        if ($response->hasArrayContent())
        {
            if (empty($_ENV['MRCORE_DBG_DATA_RAW']))
            {
                $response->setContent
                (
                    $this->_renderActionContent
                    (
                        $action,
                        $this->section->options['templater'],
                        $response->getContent()
                    )
                );
            }

            // в режиме получения RAW данных устанавливается XML заголовок
            if (ResponseInterface::CONTENT_TYPE_HTML === $httpResponse->getContentType())
            {
                $httpResponse->setContentType(ResponseInterface::CONTENT_TYPE_XML);
            }

            $httpResponse->removeHeader('Cache-Control');
        }
    }

    protected function render(): AbstractViewEngine

    /**
     * @param  array $data {@see AbstractViewEngine::$vars}
     *
     * @template T
     * @param  class-string<T>|null  $class
     * @return T
     */
    protected function _createViewEngine(string $class): AbstractViewEngine
    {
        return new $class();
    }

}