<?php declare(strict_types=1);
namespace mrcore\view;

/**
 * Реализация нативного PHP шаблонизатора.
 *
 * @author  Andrey J. Nazarov
 *
 */
class PhpEngine extends AbstractViewEngine
{
    /**
     * @inheritdoc
     */
    public function render(string $templateName, array $variables = null): string
    {
        $templatePath = $this->manager->getTemplatePath($templateName);

        if ($this->manager->showRawData())
        {
            ob_start();
            var_dump('templatePath: ' . $templatePath);
            var_dump($variables);

            return ob_get_clean();
        }

        // :WARNING: массив $_vars будет доступен в подключённом шаблоне
        $_vars = $variables ?? [];

        ob_start();
        include($templatePath);

        return ob_get_clean();
    }

}