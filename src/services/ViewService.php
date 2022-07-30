<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\base\TraitSingleton;
use mrcore\exceptions\ViewException;
use mrcore\view\AbstractViewEngine;
use mrcore\view\ViewManagerInterface;

/**
 * Сервис управления шаблонизаторами.
 *
 * @author  Andrey J. Nazarov
 */
class ViewService implements ServiceInterface, ViewManagerInterface
{
    use TraitSingleton;

    /**
     * Список созданных шаблонизаторов.
     *
     * @var  array<string, AbstractViewEngine>
     */
    private array $engines = [];

    #################################### Methods #####################################

    /**
     * @param  string $templateDir // корневая директория, где содержатся шаблоны
     */
    public function __construct(private string $templateDir, private bool $showRawData)
    {
        $this->_initSingleton();
    }

    /**
     * @inheritdoc
     */
    public function getViewEngine(string $class): AbstractViewEngine
    {
        if (!isset($this->engines))
        {
            $this->engines[$class] = $this->_createViewEngine($class);
        }

        return $this->engines[$class];
    }

    /**
     * @inheritdoc
     */
    public function getTemplatePath(string $templateName): string
    {
        $templatePath = $this->templateDir . ltrim($templateName, '/');

        if (!is_file($templatePath))
        {
            throw ViewException::templateIsNotFound($templatePath);
        }

        return $templatePath;
    }

    /**
     * @inheritdoc
     */
    public function showRawData(): bool
    {
        return $this->showRawData;
    }

    /**
     * Создаётся и возвращается указанный шаблонизатор.
     */
    protected function _createViewEngine(string $class): AbstractViewEngine
    {
        return new $class($this);
    }

}