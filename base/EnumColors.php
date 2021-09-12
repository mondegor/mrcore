<?php declare(strict_types=1);
namespace mrcore\base;

/**
 * Перечисления цветов.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/base
 */
class EnumColors
{
    /**
     * Перечисление доступных цветов.
     *
     * @const  int
     */
    public const BLACK = 1,
                 BLUE = 2,
                 CYAN = 3,
                 DARK_GRAY = 4,
                 DARK_YELLOW = 5,
                 GREEN = 6,
                 LIGHT_BLUE = 7,
                 LIGHT_CYAN = 8,
                 LIGHT_GRAY = 9,
                 LIGHT_GREEN = 10,
                 LIGHT_PURPLE = 11,
                 LIGHT_RED = 12,
                 MAGENTA = 13,
                 PURPLE = 14,
                 RED = 15,
                 WHITE = 16,
                 YELLOW = 17;

    /**
     * Перечисление двойных цветов - цвета и фона.
     * Здесь приводится набор только тех цветов,
     * которые хорошо сочитаются между собой.
     *
     * @const  int
     */
    public const BLACK_GREEN = 1,
                 BLACK_CYAN = 2,
                 BLACK_LIGHT_GRAY = 3,
                 BLACK_RED = 4,
                 BLACK_YELLOW = 5,
                 BLUE_CYAN = 6,
                 BLUE_LIGHT_GRAY = 7,
                 CYAN_BLACK = 8,
                 DARK_GRAY_BLACK = 9,
                 DARK_YELLOW_BLACK = 10,
                 GREEN_BLACK = 11,
                 LIGHT_BLUE_BLACK = 12,
                 LIGHT_CYAN_BLACK = 13,
                 LIGHT_CYAN_BLUE = 14,
                 LIGHT_GRAY_RED = 15,
                 // LIGHT_GRAY_BLACK = 0,
                 LIGHT_GRAY_BLUE = 16,
                 LIGHT_GRAY_MAGENTA = 17,
                 LIGHT_GREEN_BLACK = 18,
                 LIGHT_PURPLE_BLACK = 19,
                 LIGHT_PURPLE_LIGHT_GRAY = 20,
                 LIGHT_RED_BLACK = 21,
                 PURPLE_BLACK = 22,
                 PURPLE_LIGHT_GRAY = 23,
                 RED_BLACK = 24,
                 RED_LIGHT_GRAY = 25,
                 RED_YELLOW = 26,
                 WHITE_BLACK = 27,
                 WHITE_BLUE = 28,
                 WHITE_CYAN = 29,
                 WHITE_GREEN = 30,
                 WHITE_MAGENTA = 31,
                 WHITE_RED = 32,
                 WHITE_YELLOW = 33,
                 YELLOW_BLACK = 34,
                 YELLOW_BLUE = 35,
                 YELLOW_CYAN = 36,
                 YELLOW_GREEN = 37,
                 YELLOW_MAGENTA = 38,
                 YELLOW_RED = 39,
                 YELLOW_YELLOW = 40;
}