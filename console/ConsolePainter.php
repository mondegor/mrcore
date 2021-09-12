<?php declare(strict_types=1);
namespace mrcore\console;
use mrcore\base\EnumColors;

require_once 'mrcore/base/EnumColors.php';
require_once 'mrcore/console/AbstractPainter.php';

/**
 * Реализация класса для работы в консоле.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/console
 */
class ConsolePainter extends AbstractPainter
{
    /**
     * Завершающая строка.
     */
    private const _ENDING_LINE = "\033[0m";

    ################################### Properties ###################################

    /**
     * {@inheritdoc}
     */
    /*__override__*/ protected array $_foregroundColors = array
    (
        EnumColors::BLACK        => "\033[0;30m",
        EnumColors::DARK_GRAY    => "\033[1;30m",
        EnumColors::BLUE         => "\033[0;34m",
        EnumColors::LIGHT_BLUE   => "\033[1;34m",
        EnumColors::GREEN        => "\033[0;32m",
        EnumColors::LIGHT_GREEN  => "\033[1;32m",
        EnumColors::CYAN         => "\033[0;36m",
        EnumColors::LIGHT_CYAN   => "\033[1;36m",
        EnumColors::RED          => "\033[0;31m",
        EnumColors::LIGHT_RED    => "\033[1;31m",
        EnumColors::PURPLE       => "\033[0;35m",
        EnumColors::LIGHT_PURPLE => "\033[1;35m",
        EnumColors::DARK_YELLOW  => "\033[0;33m",
        EnumColors::YELLOW       => "\033[1;33m",
        EnumColors::LIGHT_GRAY   => '', // by default: \033[0;37m
        EnumColors::WHITE        => "\033[1;37m",
    );

    /**
     * {@inheritdoc}
     */
    /*__override__*/ protected array $_backgroundColors = array
    (
        EnumColors::BLACK      => '', // by default: \033[40m
        EnumColors::RED        => "\033[41m",
        EnumColors::GREEN      => "\033[42m",
        EnumColors::YELLOW     => "\033[43m",
        EnumColors::BLUE       => "\033[44m",
        EnumColors::MAGENTA    => "\033[45m",
        EnumColors::CYAN       => "\033[46m",
        EnumColors::LIGHT_GRAY => "\033[47m"
    );

    #################################### Methods #####################################

    /**
     * Реализация метода для правильного отображения раскрашенного текста в консоле.
     *
     * {@inheritdoc}
     */
    /*__override__*/ protected function _coloring(string $string, int $foreground, int $background): string
    {
         // если это нестандартный фон, то добавляются отступы для красоты
        if (EnumColors::BLACK !== $background)
        {
            $string = ' ' . $string . ' ';
        }

        return $this->_foregroundColors[$foreground] .
               $this->_backgroundColors[$background] .
               $string .
               self::_ENDING_LINE;
    }

}