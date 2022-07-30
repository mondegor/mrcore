<?php declare(strict_types=1);
namespace mrcore\debug;
use mrcore\console\EnumLiteral;

/**
 * Класс форматирует информацию об ошибке и связанную с ней
 * отладочную информацию виде текста и записывает её в лог файл.
 *
 * @author  Andrey J. Nazarov
 */
class FileDebuggingData extends AbstractDebuggingData
{
    /**
     * Имя лог-файла куда будут сохранятся сообщения об ошибках.
     */
    private string $pathToLog = '';

    /**
     * Логин разработчика (может содержать только латинские буквы).
     */
    private string $developerLogin = '';

    #################################### Methods #####################################

    /**
     * @param  string|null  $pathToLog (/path/to/log/)
     * @param  string|null  $developerLogin (может содержать только латинские буквы)
     */
    public function __construct(private HelperClientData $clientData,
                                string $pathToLog = null,
                                string $developerLogin = null)
    {
        if (null !== $pathToLog)
        {
            $this->pathToLog = $pathToLog;
        }

        if (null !== $developerLogin && ctype_alpha($developerLogin))
        {
            $this->developerLogin = $developerLogin . '_';
        }
    }

    /**
     * Приём информации об ошибке, форматирование её в виде текста и запись её в лог файл.
     * @inheritdoc
     */
    public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void
    {
        [$errstr, $extendedInfo] = $this->_parseError($errstr);
        $errorMessage = sprintf('%s: "%s" in %s on line %s', $this->_getTypeError($errno), $errstr, $errfile, $errline);

        $debuggingInfo = EnumLiteral::LINE_DOUBLE .
                         $this->clientData->getInfo() .
                         ' ' . $errorMessage . PHP_EOL;

        if ('' !== $extendedInfo)
        {
            $debuggingInfo .= EnumLiteral::LINE_DASH . '  ' . $extendedInfo . PHP_EOL;
        }

        $debuggingInfo .= EnumLiteral::LINE_DOUBLE .
                          ' Call Stack (Function [Location])' . PHP_EOL .
                          EnumLiteral::LINE_DASH;

        ##################################################################################

        for ($i = 0, $cnt = count($backTrace); $i < $cnt; $i++)
        {
            $item = $backTrace[$i];

            $cellFunction = ($item['class'] ?? '') .
                            ($item['type'] ?? '') .
                            (isset($item['function']) ? $item['function'] . '(' . (isset($item['args']) ? Tools::args2str(Tools::getHiddenData($item['args'], $this->clientData->getWords())) : '') . ')' : '');

            $cellLocation = (isset($item['file']) ? $item['file'] . ':' : '') .
                            ($item['line'] ?? '');

            $debuggingInfo .= '' . ($cnt - $i - 1) . ') ' .
                              '' . $cellFunction . ' ' .
                              '[' . $cellLocation . ']' . PHP_EOL .
                              EnumLiteral::LINE_DASH;
        }

        $debuggingInfo .= PHP_EOL;

        ##################################################################################

        if ('' !== $this->pathToLog)
        {
            error_log('[' . date('Y-m-d H:i:s') . '] ' . $debuggingInfo, 3, $this->pathToLog . 'mr_errors_' . $this->developerLogin . date('Y-m') . '.log');
        }
        else
        {
            error_log($debuggingInfo);
        }
    }

}