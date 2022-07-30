<?php declare(strict_types=1);
namespace mrcore\debug;
use mrcore\MrScreen;
use mrcore\console\EnumColor;
use mrcore\console\EnumLiteral;

/**
 * Класс форматирует информацию об ошибке и связанную с ней
 * отладочную информацию виде текста.
 *
 * @author  Andrey J. Nazarov
 */
class TextDebuggingData extends AbstractDebuggingData
{

    public function __construct(private HelperCodeFragment $codeFragment) { }

    /**
     * Приём информации об ошибке и форматирование её в виде текста.
     * @inheritdoc
     */
    public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void
    {
        [$errstr, $extendedInfo] = $this->_parseError($errstr);
        $errorMessage = sprintf(' %s: "%s" in %s on line %s', $this->_getTypeError($errno), $errstr, $errfile, $errline);

        $debuggingInfo = EnumLiteral::LINE_DOUBLE . MrScreen::wrapColor($errorMessage, EnumColor::LIGHT_RED_BLACK) . PHP_EOL;

        if ('' !== $extendedInfo)
        {
            $debuggingInfo .= EnumLiteral::LINE_DASH . MrScreen::wrapColor('  ' . $extendedInfo, EnumColor::YELLOW_BLACK) . PHP_EOL;
        }

        if ('' !== ($codeFragment = $this->codeFragment->getInfo($errfile, $errline)))
        {
            $debuggingInfo .= EnumLiteral::LINE_DASH . $codeFragment . PHP_EOL;
        }

        $debuggingInfo .= EnumLiteral::LINE_DOUBLE .
                          MrScreen::wrapColor('   Call Stack (Function [Location])', EnumColor::LIGHT_BLUE_BLACK) . PHP_EOL .
                          EnumLiteral::LINE_DASH;

        ##################################################################################

        for ($i = 0, $count = count($backTrace); $i < $count; $i++)
        {
            $item = $backTrace[$i];

            $cellFunction = (isset($item['class']) ? MrScreen::wrapColor($item['class'], EnumColor::WHITE_BLACK) : '') .
                            (isset($item['type']) ? MrScreen::wrapColor($item['type'], EnumColor::LIGHT_RED_BLACK) : '') .
                            (isset($item['function']) ? MrScreen::wrapColor($item['function'] . '(' . (isset($item['args']) ? MrScreen::wrapColor(Tools::args2str($item['args']), EnumColor::LIGHT_BLUE_BLACK) : '') . ')', EnumColor::YELLOW_BLACK) : '');

            $cellLocation = (isset($item['file']) ? MrScreen::wrapColor($item['file'], EnumColor::CYAN_BLACK) . ':' : '') .
                            (isset($item['line']) ? MrScreen::wrapColor((string)$item['line'], EnumColor::LIGHT_RED_BLACK) : '');

            $debuggingInfo .= '' . MrScreen::wrapColor(($count - $i - 1) . ')', EnumColor::LIGHT_RED_BLACK) .
                              ' ' . $cellFunction . ' ' .
                              '[' . $cellLocation . ']' . PHP_EOL;

            if (isset($item['file'], $item['line']) && '' !== ($codeFragment = $this->codeFragment->getInfo($item['file'], $item['line'])))
            {
                $debuggingInfo .= EnumLiteral::LINE_DASH . $codeFragment . PHP_EOL;
            }

            $debuggingInfo .= EnumLiteral::LINE_DASH;
        }

        $this->_echo($debuggingInfo . PHP_EOL);
    }

    /**
     * В случае поддержки стандартного вывода ошибок,
     * данные направляются туда, иначе выводятся обычным способом.
     */
    protected function _echo(string $message)
    {
        if (defined('STDERR'))
        {
            fwrite(STDERR, $message);
            return;
        }

        echo $message;
    }

}