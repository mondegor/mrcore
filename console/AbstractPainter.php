<?php declare(strict_types=1);
namespace mrcore\console;
use mrcore\base\EnumColors;

require_once 'mrcore/base/EnumColors.php';

/**
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/console
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
        EnumColors::BLACK_RED          => [EnumColors::BLACK, EnumColors::RED],
        EnumColors::BLACK_GREEN        => [EnumColors::BLACK, EnumColors::GREEN],
        EnumColors::BLACK_YELLOW       => [EnumColors::BLACK, EnumColors::YELLOW],
        EnumColors::BLACK_CYAN         => [EnumColors::BLACK, EnumColors::CYAN],
        EnumColors::BLACK_LIGHT_GRAY   => [EnumColors::BLACK, EnumColors::LIGHT_GRAY],
        EnumColors::DARK_GRAY_BLACK    => [EnumColors::DARK_GRAY, EnumColors::BLACK],
        EnumColors::BLUE_CYAN          => [EnumColors::BLUE, EnumColors::CYAN],
        EnumColors::BLUE_LIGHT_GRAY    => [EnumColors::BLUE, EnumColors::LIGHT_GRAY],
        EnumColors::LIGHT_BLUE_BLACK   => [EnumColors::LIGHT_BLUE, EnumColors::BLACK],
        EnumColors::GREEN_BLACK        => [EnumColors::GREEN, EnumColors::BLACK],
        EnumColors::LIGHT_GREEN_BLACK  => [EnumColors::LIGHT_GREEN, EnumColors::BLACK],
        EnumColors::CYAN_BLACK         => [EnumColors::CYAN, EnumColors::BLACK],
        EnumColors::LIGHT_CYAN_BLACK   => [EnumColors::LIGHT_CYAN, EnumColors::BLACK],
        EnumColors::LIGHT_CYAN_BLUE    => [EnumColors::LIGHT_CYAN, EnumColors::BLUE],
        EnumColors::RED_BLACK          => [EnumColors::RED, EnumColors::BLACK],
        EnumColors::RED_YELLOW         => [EnumColors::RED, EnumColors::YELLOW],
        EnumColors::RED_LIGHT_GRAY     => [EnumColors::RED, EnumColors::LIGHT_GRAY],
        EnumColors::LIGHT_RED_BLACK    => [EnumColors::LIGHT_RED, EnumColors::BLACK],
        EnumColors::PURPLE_BLACK       => [EnumColors::PURPLE, EnumColors::BLACK],
        EnumColors::PURPLE_LIGHT_GRAY  => [EnumColors::PURPLE, EnumColors::LIGHT_GRAY],
        EnumColors::LIGHT_PURPLE_BLACK => [EnumColors::LIGHT_PURPLE, EnumColors::BLACK],
        EnumColors::LIGHT_PURPLE_LIGHT_GRAY => [EnumColors::LIGHT_PURPLE, EnumColors::LIGHT_GRAY],
        EnumColors::DARK_YELLOW_BLACK  => [EnumColors::DARK_YELLOW, EnumColors::BLACK],
        EnumColors::YELLOW_BLACK       => [EnumColors::YELLOW, EnumColors::BLACK],
        EnumColors::YELLOW_RED         => [EnumColors::YELLOW, EnumColors::RED],
        EnumColors::YELLOW_GREEN       => [EnumColors::YELLOW, EnumColors::GREEN],
        EnumColors::YELLOW_YELLOW      => [EnumColors::YELLOW, EnumColors::YELLOW],
        EnumColors::YELLOW_BLUE        => [EnumColors::YELLOW, EnumColors::BLUE],
        EnumColors::YELLOW_MAGENTA     => [EnumColors::YELLOW, EnumColors::MAGENTA],
        EnumColors::YELLOW_CYAN        => [EnumColors::YELLOW, EnumColors::CYAN],
        // EnumColors::LIGHT_GRAY_BLACK   => [EnumColors::LIGHT_GRAY, EnumColors::BLACK],
        EnumColors::LIGHT_GRAY_RED     => [EnumColors::LIGHT_GRAY, EnumColors::RED],
        EnumColors::LIGHT_GRAY_BLUE    => [EnumColors::LIGHT_GRAY, EnumColors::BLUE],
        EnumColors::LIGHT_GRAY_MAGENTA => [EnumColors::LIGHT_GRAY, EnumColors::MAGENTA],
        EnumColors::WHITE_BLACK        => [EnumColors::WHITE, EnumColors::BLACK],
        EnumColors::WHITE_RED          => [EnumColors::WHITE, EnumColors::RED],
        EnumColors::WHITE_GREEN        => [EnumColors::WHITE, EnumColors::GREEN],
        EnumColors::WHITE_YELLOW       => [EnumColors::WHITE, EnumColors::YELLOW],
        EnumColors::WHITE_BLUE         => [EnumColors::WHITE, EnumColors::BLUE],
        EnumColors::WHITE_MAGENTA      => [EnumColors::WHITE, EnumColors::MAGENTA],
        EnumColors::WHITE_CYAN         => [EnumColors::WHITE, EnumColors::CYAN]
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