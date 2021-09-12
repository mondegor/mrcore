<?php declare(strict_types=1);
namespace mrcore\console;
use mrcore\base\EnumColors;

require_once 'mrcore/base/EnumColors.php';
require_once 'mrcore/console/AbstractPainter.php';

/**
 * Реализация класса для работы с HTML документом.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/console
 */
class HtmlPainter extends AbstractPainter
{

    ################################### Properties ###################################

    /**
     * {@inheritdoc}
     */
    /*__override__*/ protected array $_foregroundColors = array
    (
        EnumColors::BLACK        => '#000000',
        EnumColors::DARK_GRAY    => '#767676',
        EnumColors::BLUE         => '#0037DA',
        EnumColors::LIGHT_BLUE   => '#3B78FF',
        EnumColors::GREEN        => '#13A10E',
        EnumColors::LIGHT_GREEN  => '#16C60C',
        EnumColors::CYAN         => '#3A96DD',
        EnumColors::LIGHT_CYAN   => '#61D6D6',
        EnumColors::RED          => '#C50F1F',
        EnumColors::LIGHT_RED    => '#E74856',
        EnumColors::PURPLE       => '#881798',
        EnumColors::LIGHT_PURPLE => '#B4009E',
        EnumColors::DARK_YELLOW  => '#C19C00',
        EnumColors::YELLOW       => '#F9F1A5',
        EnumColors::LIGHT_GRAY   => '#CCCCCC',
        EnumColors::WHITE        => '#F2F2F2',
    );

    /**
     * {@inheritdoc}
     */
    /*__override__*/ protected array $_backgroundColors = array
    (
        EnumColors::BLACK      => '#000000',
        EnumColors::RED        => '#C50F1F',
        EnumColors::GREEN      => '#13A10E',
        EnumColors::YELLOW     => '#C19C00',
        EnumColors::BLUE       => '#0037DA',
        EnumColors::MAGENTA    => '#881798',
        EnumColors::CYAN       => '#3A96DD',
        EnumColors::LIGHT_GRAY => '#CCCCCC',
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
        if (EnumColors::BLACK !== $background)
        {
            $string = '&nbsp;' . str_replace(' ', '&nbsp;', $string) . '&nbsp;';
        }

        return sprintf('<span style="color: %s; background-color: %s">%s</span>',
                           $this->_foregroundColors[$foreground],
                           $this->_backgroundColors[$background],
                           $string);
    }

}