<?php declare(strict_types=1);
namespace mrcore\console;

/**
 * Абстракция окрашивания данных.
 *
 * С помощью метода {@see AbstractPainter::colorList()} можно вывести
 * все варианты окрашивания данных конкретным классом.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractPainter
{
    /**
     * Тестовая строка, для вывода палитры поддерживаемых цветов классом.
     */
    private const TEST_STRING = 'This is a string to show the color and background';

    /**
     * Отображение двойного цвета в отдельные: цвет и фон.
     *
     * @var  array [int => [int, int], ...]
     */
    public const FOREGROUND_BACKGROUND = array
    (
        EnumColor::BLACK_RED          => [EnumColor::BLACK, EnumColor::RED],
        EnumColor::BLACK_GREEN        => [EnumColor::BLACK, EnumColor::GREEN],
        EnumColor::BLACK_YELLOW       => [EnumColor::BLACK, EnumColor::YELLOW],
        EnumColor::BLACK_CYAN         => [EnumColor::BLACK, EnumColor::CYAN],
        EnumColor::BLACK_LIGHT_GRAY   => [EnumColor::BLACK, EnumColor::LIGHT_GRAY],
        EnumColor::DARK_GRAY_BLACK    => [EnumColor::DARK_GRAY, EnumColor::BLACK],
        EnumColor::BLUE_CYAN          => [EnumColor::BLUE, EnumColor::CYAN],
        EnumColor::BLUE_LIGHT_GRAY    => [EnumColor::BLUE, EnumColor::LIGHT_GRAY],
        EnumColor::LIGHT_BLUE_BLACK   => [EnumColor::LIGHT_BLUE, EnumColor::BLACK],
        EnumColor::GREEN_BLACK        => [EnumColor::GREEN, EnumColor::BLACK],
        EnumColor::LIGHT_GREEN_BLACK  => [EnumColor::LIGHT_GREEN, EnumColor::BLACK],
        EnumColor::CYAN_BLACK         => [EnumColor::CYAN, EnumColor::BLACK],
        EnumColor::LIGHT_CYAN_BLACK   => [EnumColor::LIGHT_CYAN, EnumColor::BLACK],
        EnumColor::LIGHT_CYAN_BLUE    => [EnumColor::LIGHT_CYAN, EnumColor::BLUE],
        EnumColor::RED_BLACK          => [EnumColor::RED, EnumColor::BLACK],
        EnumColor::RED_YELLOW         => [EnumColor::RED, EnumColor::YELLOW],
        EnumColor::RED_LIGHT_GRAY     => [EnumColor::RED, EnumColor::LIGHT_GRAY],
        EnumColor::LIGHT_RED_BLACK    => [EnumColor::LIGHT_RED, EnumColor::BLACK],
        EnumColor::PURPLE_BLACK       => [EnumColor::PURPLE, EnumColor::BLACK],
        EnumColor::PURPLE_LIGHT_GRAY  => [EnumColor::PURPLE, EnumColor::LIGHT_GRAY],
        EnumColor::LIGHT_PURPLE_BLACK => [EnumColor::LIGHT_PURPLE, EnumColor::BLACK],
        EnumColor::LIGHT_PURPLE_LIGHT_GRAY => [EnumColor::LIGHT_PURPLE, EnumColor::LIGHT_GRAY],
        EnumColor::DARK_YELLOW_BLACK  => [EnumColor::DARK_YELLOW, EnumColor::BLACK],
        EnumColor::YELLOW_BLACK       => [EnumColor::YELLOW, EnumColor::BLACK],
        EnumColor::YELLOW_RED         => [EnumColor::YELLOW, EnumColor::RED],
        EnumColor::YELLOW_GREEN       => [EnumColor::YELLOW, EnumColor::GREEN],
        EnumColor::YELLOW_YELLOW      => [EnumColor::YELLOW, EnumColor::YELLOW],
        EnumColor::YELLOW_BLUE        => [EnumColor::YELLOW, EnumColor::BLUE],
        EnumColor::YELLOW_MAGENTA     => [EnumColor::YELLOW, EnumColor::MAGENTA],
        EnumColor::YELLOW_CYAN        => [EnumColor::YELLOW, EnumColor::CYAN],
        // EnumColor::LIGHT_GRAY_BLACK   => [EnumColor::LIGHT_GRAY, EnumColor::BLACK],
        EnumColor::LIGHT_GRAY_RED     => [EnumColor::LIGHT_GRAY, EnumColor::RED],
        EnumColor::LIGHT_GRAY_BLUE    => [EnumColor::LIGHT_GRAY, EnumColor::BLUE],
        EnumColor::LIGHT_GRAY_MAGENTA => [EnumColor::LIGHT_GRAY, EnumColor::MAGENTA],
        EnumColor::WHITE_BLACK        => [EnumColor::WHITE, EnumColor::BLACK],
        EnumColor::WHITE_RED          => [EnumColor::WHITE, EnumColor::RED],
        EnumColor::WHITE_GREEN        => [EnumColor::WHITE, EnumColor::GREEN],
        EnumColor::WHITE_YELLOW       => [EnumColor::WHITE, EnumColor::YELLOW],
        EnumColor::WHITE_BLUE         => [EnumColor::WHITE, EnumColor::BLUE],
        EnumColor::WHITE_MAGENTA      => [EnumColor::WHITE, EnumColor::MAGENTA],
        EnumColor::WHITE_CYAN         => [EnumColor::WHITE, EnumColor::CYAN]
    );

    ################################### Properties ###################################

    /**
     * Массив отображений номера цвета в код цвета.
     *
     * @var   array [int => string], ...]
     */
    protected array $foregroundColors;

    /**
     * Массив отображений номера фона в код фона.
     *
     * @var   array [[int => string], ...]
     */
    protected array $backgroundColors;

    #################################### Methods #####################################

    /**
     * Окраска указанной строки в указанный цвет-фон.
     *
     * @param     string  $string
     * @param     int     $doubleColor
     * @return    string
     */
    public function coloring(string $string, int $doubleColor): string
    {
        assert(isset(self::FOREGROUND_BACKGROUND[$doubleColor]));

        if (!isset(self::FOREGROUND_BACKGROUND[$doubleColor]))
        {
            return $string;
        }

        [$foreground, $background] = self::FOREGROUND_BACKGROUND[$doubleColor];

        return $this->_coloring($string, $foreground, $background);
    }

    /**
     * Вывод палитры поддерживаемых цветов классом.
     */
    public function colorList(): void
    {
        foreach(self::FOREGROUND_BACKGROUND as $item)
        {
            echo sprintf("face: %u; back: %u; %s\n", $item[0], $item[1], $this->_coloring(self::TEST_STRING, $item[0], $item[1]));
        }
    }

    ##################################################################################

    /**
     * Окраска указанной строки в указанные цвет и фон.
     *
     * @param     string  $string
     * @param     int     $foreground
     * @param     int     $background
     * @return    string
     */
    abstract protected function _coloring(string $string, int $foreground, int $background): string;

}