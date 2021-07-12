<?php declare(strict_types=1);
namespace mrcore\console;

require_once 'mrcore/console/AbstractPainter.php';

/**
 * Реализация класса для работы в консоле.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore.console
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
        COLOR_BLACK        => "\033[0;30m",
        COLOR_DARK_GRAY    => "\033[1;30m",
        COLOR_BLUE         => "\033[0;34m",
        COLOR_LIGHT_BLUE   => "\033[1;34m",
        COLOR_GREEN        => "\033[0;32m",
        COLOR_LIGHT_GREEN  => "\033[1;32m",
        COLOR_CYAN         => "\033[0;36m",
        COLOR_LIGHT_CYAN   => "\033[1;36m",
        COLOR_RED          => "\033[0;31m",
        COLOR_LIGHT_RED    => "\033[1;31m",
        COLOR_PURPLE       => "\033[0;35m",
        COLOR_LIGHT_PURPLE => "\033[1;35m",
        COLOR_DARK_YELLOW  => "\033[0;33m",
        COLOR_YELLOW       => "\033[1;33m",
        COLOR_LIGHT_GRAY   => '', // by default: \033[0;37m
        COLOR_WHITE        => "\033[1;37m",
    );

    /**
     * {@inheritdoc}
     */
    /*__override__*/ protected array $_backgroundColors = array
    (
        COLOR_BLACK      => '', // by default: \033[40m
        COLOR_RED        => "\033[41m",
        COLOR_GREEN      => "\033[42m",
        COLOR_YELLOW     => "\033[43m",
        COLOR_BLUE       => "\033[44m",
        COLOR_MAGENTA    => "\033[45m",
        COLOR_CYAN       => "\033[46m",
        COLOR_LIGHT_GRAY => "\033[47m"
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
        if (COLOR_BLACK !== $background)
        {
            $string = ' ' . $string . ' ';
        }

        return $this->_foregroundColors[$foreground] .
               $this->_backgroundColors[$background] .
               $string .
               self::_ENDING_LINE;
    }

}