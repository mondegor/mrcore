<?php declare(strict_types=1);
namespace mrcore\console;

/**
 * Реализация класса для окрашивания данных в HTML документах.
 *
 * @author  Andrey J. Nazarov
 */
class HtmlPainter extends AbstractPainter
{
    /**
     * @inheritdoc
     */
    protected array $foregroundColors = array
    (
        EnumColor::BLACK        => '#000000',
        EnumColor::DARK_GRAY    => '#767676',
        EnumColor::BLUE         => '#0037DA',
        EnumColor::LIGHT_BLUE   => '#3B78FF',
        EnumColor::GREEN        => '#13A10E',
        EnumColor::LIGHT_GREEN  => '#16C60C',
        EnumColor::CYAN         => '#3A96DD',
        EnumColor::LIGHT_CYAN   => '#61D6D6',
        EnumColor::RED          => '#C50F1F',
        EnumColor::LIGHT_RED    => '#E74856',
        EnumColor::PURPLE       => '#881798',
        EnumColor::LIGHT_PURPLE => '#B4009E',
        EnumColor::DARK_YELLOW  => '#C19C00',
        EnumColor::YELLOW       => '#F9F1A5',
        EnumColor::LIGHT_GRAY   => '#CCCCCC',
        EnumColor::WHITE        => '#F2F2F2',
    );

    /**
     * @inheritdoc
     */
    protected array $backgroundColors = array
    (
        EnumColor::BLACK      => '#000000',
        EnumColor::RED        => '#C50F1F',
        EnumColor::GREEN      => '#13A10E',
        EnumColor::YELLOW     => '#C19C00',
        EnumColor::BLUE       => '#0037DA',
        EnumColor::MAGENTA    => '#881798',
        EnumColor::CYAN       => '#3A96DD',
        EnumColor::LIGHT_GRAY => '#CCCCCC',
    );

    #################################### Methods #####################################

    /**
     * Реализация метода для правильного отображения раскрашенного текста в HTML документе.
     *
     * @inheritdoc
     */
    protected function _coloring(string $string, int $foreground, int $background): string
    {
        // если это нестандартный фон, то добавляются отступы для красоты
        if (EnumColor::BLACK !== $background)
        {
            $string = '&nbsp;' . str_replace(' ', '&nbsp;', $string) . '&nbsp;';
        }

        return sprintf('<span style="color: %s; background-color: %s">%s</span>',
                           $this->foregroundColors[$foreground],
                           $this->backgroundColors[$background],
                           $string);
    }

}