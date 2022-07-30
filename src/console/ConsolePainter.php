<?php declare(strict_types=1);
namespace mrcore\console;

/**
 * Реализация класса для окрашивания данных при выводе их в консоль.
 *
 * @author  Andrey J. Nazarov
 */
class ConsolePainter extends AbstractPainter
{
    /**
     * Завершающая строка.
     */
    private const ENDING_LINE = "\033[0m";

    ################################### Properties ###################################

    /**
     * @inheritdoc
     */
    protected array $foregroundColors = array
    (
        EnumColor::BLACK        => "\033[0;30m",
        EnumColor::DARK_GRAY    => "\033[1;30m",
        EnumColor::BLUE         => "\033[0;34m",
        EnumColor::LIGHT_BLUE   => "\033[1;34m",
        EnumColor::GREEN        => "\033[0;32m",
        EnumColor::LIGHT_GREEN  => "\033[1;32m",
        EnumColor::CYAN         => "\033[0;36m",
        EnumColor::LIGHT_CYAN   => "\033[1;36m",
        EnumColor::RED          => "\033[0;31m",
        EnumColor::LIGHT_RED    => "\033[1;31m",
        EnumColor::PURPLE       => "\033[0;35m",
        EnumColor::LIGHT_PURPLE => "\033[1;35m",
        EnumColor::DARK_YELLOW  => "\033[0;33m",
        EnumColor::YELLOW       => "\033[1;33m",
        EnumColor::LIGHT_GRAY   => '', // by default: \033[0;37m
        EnumColor::WHITE        => "\033[1;37m",
    );

    /**
     * @inheritdoc
     */
    protected array $backgroundColors = array
    (
        EnumColor::BLACK      => '', // by default: \033[40m
        EnumColor::RED        => "\033[41m",
        EnumColor::GREEN      => "\033[42m",
        EnumColor::YELLOW     => "\033[43m",
        EnumColor::BLUE       => "\033[44m",
        EnumColor::MAGENTA    => "\033[45m",
        EnumColor::CYAN       => "\033[46m",
        EnumColor::LIGHT_GRAY => "\033[47m"
    );

    #################################### Methods #####################################

    /**
     * Реализация метода для правильного отображения раскрашенного текста в консоле.
     *
     * @inheritdoc
     */
    protected function _coloring(string $string, int $foreground, int $background): string
    {
        $chars = $this->foregroundColors[$foreground] . $this->backgroundColors[$background];
        $string = str_replace(self::ENDING_LINE, self::ENDING_LINE . $chars, $string);

        //// если это нестандартный фон, то добавляются отступы для красоты
        //if (EnumColor::BLACK !== $background)
        //{
        //    if ('' !== trim(substr($string, -1), " \n\r"))
        //    {
        //        $string .= ' ';
        //    }
        //
        //    if ('' !== trim(substr($string, 0, 1), " \n\r"))
        //    {
        //        $string = ' ' . $string;
        //    }
        //}

        return $chars . $string . self::ENDING_LINE;
    }

}