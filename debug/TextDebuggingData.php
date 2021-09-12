<?php declare(strict_types=1);
namespace mrcore\debug;

require_once 'mrcore/Constants.php';
require_once 'mrcore/debug/AbstractDebuggingData.php';
require_once 'mrcore/debug/Tools.php';

/**
 * Класс форматирует информацию об ошибке и связанную с ней
 * отладочную информацию виде текста.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/debug
 */
class TextDebuggingData extends AbstractDebuggingData
{
    /**
     * Приём информации об ошибке и форматирование её в виде текста.
     *
     * {@inheritdoc}
     */
    /*__override__*/ public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void
    {
        $sqlQuery = '';

        if (preg_match('/(.*) SQL Query: (.*)/', $errstr, $m) > 0)
        {
            $errstr = $m[1];
            $sqlQuery = str_replace('__BR__', "\n  ", $m[2]);
        }

        ##################################################################################

        $errorMessage = sprintf('%s: "%s" in %s on line %s', self::getTypeError($errno), $errstr, $errfile, $errline);

        $debuggingInfo = MRCORE_LINE_DOUBLE . ' ' . $errorMessage . PHP_EOL;

        if ('' !== $sqlQuery)
        {
            $debuggingInfo .= MRCORE_LINE_DASH . '  ' . $sqlQuery . PHP_EOL;
        }

        $debuggingInfo .= MRCORE_LINE_DOUBLE .
                          ' Call Stack (Function [Location])' . PHP_EOL .
                          MRCORE_LINE_DASH;

        ##################################################################################

        for ($i = 0, $cnt = count($backTrace); $i < $cnt; $i++)
        {
            $item = &$backTrace[$i];

            $cellFunction = ($item['class'] ?? '') .
                            ($item['type'] ?? '') .
                            (isset($item['function']) ? $item['function'] . '(' . (isset($item['args']) ? Tools::args2str($item['args']) : '') . ')' : '');

            $cellLocation = (isset($item['file']) ? $item['file'] . ':' : '') .
                            ($item['line'] ?? '');

            $debuggingInfo .= '' . ($cnt - $i - 1) . ') ' .
                              '' . $cellFunction . ' ' .
                              '[' . $cellLocation . ']' . PHP_EOL .
                              MRCORE_LINE_DASH;
        }

        echo $debuggingInfo . PHP_EOL;
    }

}