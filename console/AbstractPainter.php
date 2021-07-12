<?php declare(strict_types=1);
namespace mrcore\console;

/**
 * Перечисление доступных цветов.
 *
 * @const  int
 */
const COLOR_BLACK = 1,
      COLOR_BLUE = 2,
      COLOR_CYAN = 3,
      COLOR_DARK_GRAY = 4,
      COLOR_DARK_YELLOW = 5,
      COLOR_GREEN = 6,
      COLOR_LIGHT_BLUE = 7,
      COLOR_LIGHT_CYAN = 8,
      COLOR_LIGHT_GRAY = 9,
      COLOR_LIGHT_GREEN = 10,
      COLOR_LIGHT_PURPLE = 11,
      COLOR_LIGHT_RED = 12,
      COLOR_MAGENTA = 13,
      COLOR_PURPLE = 14,
      COLOR_RED = 15,
      COLOR_WHITE = 16,
      COLOR_YELLOW = 17;

/**
 * Перечисление двойных цветов - цвета и фона.
 * Здесь приводится набор только тех цветов,
 * которые хорошо сочитаются между собой.
 *
 * @const  int
 */
const DCOLOR_BLACK_GREEN = 1,
      DCOLOR_BLACK_CYAN = 2,
      DCOLOR_BLACK_LIGHT_GRAY = 3,
      DCOLOR_BLACK_RED = 4,
      DCOLOR_BLACK_YELLOW = 5,
      DCOLOR_BLUE_CYAN = 6,
      DCOLOR_BLUE_LIGHT_GRAY = 7,
      DCOLOR_CYAN_BLACK = 8,
      DCOLOR_DARK_GRAY_BLACK = 9,
      DCOLOR_DARK_YELLOW_BLACK = 10,
      DCOLOR_GREEN_BLACK = 11,
      DCOLOR_LIGHT_BLUE_BLACK = 12,
      DCOLOR_LIGHT_CYAN_BLACK = 13,
      DCOLOR_LIGHT_CYAN_BLUE = 14,
      DCOLOR_LIGHT_GRAY_RED = 15,
      // DCOLOR_LIGHT_GRAY_BLACK = 0,
      DCOLOR_LIGHT_GRAY_BLUE = 16,
      DCOLOR_LIGHT_GRAY_MAGENTA = 17,
      DCOLOR_LIGHT_GREEN_BLACK = 18,
      DCOLOR_LIGHT_PURPLE_BLACK = 19,
      DCOLOR_LIGHT_PURPLE_LIGHT_GRAY = 20,
      DCOLOR_LIGHT_RED_BLACK = 21,
      DCOLOR_PURPLE_BLACK = 22,
      DCOLOR_PURPLE_LIGHT_GRAY = 23,
      DCOLOR_RED_BLACK = 24,
      DCOLOR_RED_LIGHT_GRAY = 25,
      DCOLOR_RED_YELLOW = 26,
      DCOLOR_WHITE_BLACK = 27,
      DCOLOR_WHITE_BLUE = 28,
      DCOLOR_WHITE_CYAN = 29,
      DCOLOR_WHITE_GREEN = 30,
      DCOLOR_WHITE_MAGENTA = 31,
      DCOLOR_WHITE_RED = 32,
      DCOLOR_WHITE_YELLOW = 33,
      DCOLOR_YELLOW_BLACK = 34,
      DCOLOR_YELLOW_BLUE = 35,
      DCOLOR_YELLOW_CYAN = 36,
      DCOLOR_YELLOW_GREEN = 37,
      DCOLOR_YELLOW_MAGENTA = 38,
      DCOLOR_YELLOW_RED = 39,
      DCOLOR_YELLOW_YELLOW = 40;

/**
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore.console
 */
abstract class AbstractPainter
{
    /**
     * Тестовая строка, для вывода палитры поддерживаемых цветов классом.
     *
     * @const  string
     */
    private const _TEST_STRING = 'This is a string to show the color and background';

    /**
     * Отображение двойного цвета в отдельные: цвет и фон.
     *
     * @const  array [int => [int, int], ...]
     */
    public const FOREGROUND_BACKGROUND = array
    (
        DCOLOR_BLACK_RED          => [COLOR_BLACK, COLOR_RED],
        DCOLOR_BLACK_GREEN        => [COLOR_BLACK, COLOR_GREEN],
        DCOLOR_BLACK_YELLOW       => [COLOR_BLACK, COLOR_YELLOW],
        DCOLOR_BLACK_CYAN         => [COLOR_BLACK, COLOR_CYAN],
        DCOLOR_BLACK_LIGHT_GRAY   => [COLOR_BLACK, COLOR_LIGHT_GRAY],
        DCOLOR_DARK_GRAY_BLACK    => [COLOR_DARK_GRAY, COLOR_BLACK],
        DCOLOR_BLUE_CYAN          => [COLOR_BLUE, COLOR_CYAN],
        DCOLOR_BLUE_LIGHT_GRAY    => [COLOR_BLUE, COLOR_LIGHT_GRAY],
        DCOLOR_LIGHT_BLUE_BLACK   => [COLOR_LIGHT_BLUE, COLOR_BLACK],
        DCOLOR_GREEN_BLACK        => [COLOR_GREEN, COLOR_BLACK],
        DCOLOR_LIGHT_GREEN_BLACK  => [COLOR_LIGHT_GREEN, COLOR_BLACK],
        DCOLOR_CYAN_BLACK         => [COLOR_CYAN, COLOR_BLACK],
        DCOLOR_LIGHT_CYAN_BLACK   => [COLOR_LIGHT_CYAN, COLOR_BLACK],
        DCOLOR_LIGHT_CYAN_BLUE    => [COLOR_LIGHT_CYAN, COLOR_BLUE],
        DCOLOR_RED_BLACK          => [COLOR_RED, COLOR_BLACK],
        DCOLOR_RED_YELLOW         => [COLOR_RED, COLOR_YELLOW],
        DCOLOR_RED_LIGHT_GRAY     => [COLOR_RED, COLOR_LIGHT_GRAY],
        DCOLOR_LIGHT_RED_BLACK    => [COLOR_LIGHT_RED, COLOR_BLACK],
        DCOLOR_PURPLE_BLACK       => [COLOR_PURPLE, COLOR_BLACK],
        DCOLOR_PURPLE_LIGHT_GRAY  => [COLOR_PURPLE, COLOR_LIGHT_GRAY],
        DCOLOR_LIGHT_PURPLE_BLACK => [COLOR_LIGHT_PURPLE, COLOR_BLACK],
        DCOLOR_LIGHT_PURPLE_LIGHT_GRAY => [COLOR_LIGHT_PURPLE, COLOR_LIGHT_GRAY],
        DCOLOR_DARK_YELLOW_BLACK  => [COLOR_DARK_YELLOW, COLOR_BLACK],
        DCOLOR_YELLOW_BLACK       => [COLOR_YELLOW, COLOR_BLACK],
        DCOLOR_YELLOW_RED         => [COLOR_YELLOW, COLOR_RED],
        DCOLOR_YELLOW_GREEN       => [COLOR_YELLOW, COLOR_GREEN],
        DCOLOR_YELLOW_YELLOW      => [COLOR_YELLOW, COLOR_YELLOW],
        DCOLOR_YELLOW_BLUE        => [COLOR_YELLOW, COLOR_BLUE],
        DCOLOR_YELLOW_MAGENTA     => [COLOR_YELLOW, COLOR_MAGENTA],
        DCOLOR_YELLOW_CYAN        => [COLOR_YELLOW, COLOR_CYAN],
        // DCOLOR_LIGHT_GRAY_BLACK   => [COLOR_LIGHT_GRAY, COLOR_BLACK],
        DCOLOR_LIGHT_GRAY_RED     => [COLOR_LIGHT_GRAY, COLOR_RED],
        DCOLOR_LIGHT_GRAY_BLUE    => [COLOR_LIGHT_GRAY, COLOR_BLUE],
        DCOLOR_LIGHT_GRAY_MAGENTA => [COLOR_LIGHT_GRAY, COLOR_MAGENTA],
        DCOLOR_WHITE_BLACK        => [COLOR_WHITE, COLOR_BLACK],
        DCOLOR_WHITE_RED          => [COLOR_WHITE, COLOR_RED],
        DCOLOR_WHITE_GREEN        => [COLOR_WHITE, COLOR_GREEN],
        DCOLOR_WHITE_YELLOW       => [COLOR_WHITE, COLOR_YELLOW],
        DCOLOR_WHITE_BLUE         => [COLOR_WHITE, COLOR_BLUE],
        DCOLOR_WHITE_MAGENTA      => [COLOR_WHITE, COLOR_MAGENTA],
        DCOLOR_WHITE_CYAN         => [COLOR_WHITE, COLOR_CYAN]
    );

    ################################### Properties ###################################

    /**
     * Массив отображений номера цвета в код цвета.
     *
     * @var   array [int => string], ...]
     */
    protected array $_foregroundColors = array();

    /**
     * Массив отображений номера фона в код фона.
     *
     * @var   array [[int => string], ...]
     */
    protected array $_backgroundColors = array();

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
     * Окраска указанной строки в указанные цвет и фон.
     *
     * @param     string  $string
     * @param     int     $foreground
     * @param     int     $background
     * @return    string
     */
    abstract protected function _coloring(string $string, int $foreground, int $background): string;

    /**
     * Список зарегистрированных цветов.
     */
    public function colorList(): void
    {
        foreach(self::FOREGROUND_BACKGROUND as $item)
        {
            echo sprintf("face: %u; back: %u; %s\n", $item[0], $item[1], $this->_coloring(self::_TEST_STRING, $item[0], $item[1]));
        }
    }

}