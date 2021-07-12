<?php declare(strict_types=1);
namespace mrcore\console;

require_once 'mrcore/console/ConsolePainter.php';

/**
 * Реализация класса для работы с HTML документом.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore.console
 */
class HtmlPainter extends AbstractPainter
{

    ################################### Properties ###################################

    /**
     * {@inheritdoc}
     */
    /*__override__*/ protected array $_foregroundColors = array
    (
        COLOR_BLACK        => '#000000',
        COLOR_DARK_GRAY    => '#767676',
        COLOR_BLUE         => '#0037DA',
        COLOR_LIGHT_BLUE   => '#3B78FF',
        COLOR_GREEN        => '#13A10E',
        COLOR_LIGHT_GREEN  => '#16C60C',
        COLOR_CYAN         => '#3A96DD',
        COLOR_LIGHT_CYAN   => '#61D6D6',
        COLOR_RED          => '#C50F1F',
        COLOR_LIGHT_RED    => '#E74856',
        COLOR_PURPLE       => '#881798',
        COLOR_LIGHT_PURPLE => '#B4009E',
        COLOR_DARK_YELLOW  => '#C19C00',
        COLOR_YELLOW       => '#F9F1A5',
        COLOR_LIGHT_GRAY   => '#CCCCCC',
        COLOR_WHITE        => '#F2F2F2',
    );

    /**
     * {@inheritdoc}
     */
    /*__override__*/ protected array $_backgroundColors = array
    (
        COLOR_BLACK      => '#000000',
        COLOR_RED        => '#C50F1F',
        COLOR_GREEN      => '#13A10E',
        COLOR_YELLOW     => '#C19C00',
        COLOR_BLUE       => '#0037DA',
        COLOR_MAGENTA    => '#881798',
        COLOR_CYAN       => '#3A96DD',
        COLOR_LIGHT_GRAY => '#CCCCCC',
    );

    #################################### Methods #####################################

    /**
     * Реализация метода для правильного отображения раскрашенного текста в HTML документе.
     *
     * {@inheritdoc}
     */
    /*__override__*/ protected function _coloring(string $string, int $foreground, int $background): string
    {
        // если это нестандартный фон, то добавляются отступы для красоты
        if (COLOR_BLACK !== $background)
        {
            $string = '&nbsp;' . str_replace(' ', '&nbsp;', $string) . '&nbsp;';
        }

        return sprintf('<span style="color: %s; background-color: %s">%s</span>',
                           $this->_foregroundColors[$foreground],
                           $this->_backgroundColors[$background],
                           $string);
    }

}