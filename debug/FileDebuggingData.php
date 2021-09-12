<?php declare(strict_types=1);
namespace mrcore\debug;
use mrcore\services\EnvService;

require_once 'mrcore/Constants.php';
require_once 'mrcore/debug/AbstractDebuggingData.php';
require_once 'mrcore/debug/Tools.php';

/**
 * Класс форматирует информацию об ошибке и связанную с ней
 * отладочную информацию виде текста и записывает её в лог файл.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/debug
 */
class FileDebuggingData extends AbstractDebuggingData
{
    /**
     * Имя лог-файла куда будут сохранятся сообщения об ошибках.
     *
     * @var    string
     */
    private string $_pathToLog = '';

    /**
     * Логин разработчика (может содержать только латинские буквы).
     *
     * @var    string
     */
    private string $_developerLogin = '';

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      string  $pathToLog OPTIONAL (/path/to/log/)
     * @param      string  $developerLogin OPTIONAL (может содержать только латинские буквы)
     */
    public function __construct(string $pathToLog = null, string $developerLogin = null)
    {
        if (null !== $pathToLog)
        {
            $this->_pathToLog = $pathToLog;
        }

        if (null !== $developerLogin && ctype_alpha($developerLogin))
        {
            $this->_developerLogin = $developerLogin . '_';
        }
    }

    /**
     * Приём информации об ошибке, форматирование её в виде текста и запись её в лог файл.
     *
     * {@inheritdoc}
     */
    /*__override__*/ public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void
    {
        /* @var $env EnvService */ $env = &$this->injectService('global.env');

        $sqlQuery = '';

        if (preg_match('/(.*) SQL Query: (.*)/', $errstr, $m) > 0)
        {
            $errstr = $m[1];
            $sqlQuery = str_replace('__BR__', "\n  ", $m[2]);
        }

        ##################################################################################

        $errorMessage = sprintf('%s: "%s" in %s on line %s', self::getTypeError($errno), $errstr, $errfile, $errline);

        $ips = $env->getUserIP();

        $debuggingInfo = MRCORE_LINE_DOUBLE .
                         (0 === $ips['ip_real'] ? '' :
                             'Client: ' . $ips['string'] . '; URL: ' . $env->getRequestUrl() . PHP_EOL .
                             'User Agent: ' . $env->getUserAgent() . PHP_EOL .
                             'Referer URL: ' . $env->getRefererUrl() . PHP_EOL) .
                         (empty($_REQUEST) ? '' : (' $_REQUEST = ' . rtrim(var_export(Tools::getHiddenData($_REQUEST, self::WORDS_TO_HIDE), true), ')') . " );\n")) .
                         ' ' . $errorMessage . PHP_EOL;

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
                            (isset($item['function']) ? $item['function'] . '(' . (isset($item['args']) ? Tools::args2str(Tools::getHiddenData($item['args'], self::WORDS_TO_HIDE)) : '') . ')' : '');

            $cellLocation = (isset($item['file']) ? $item['file'] . ':' : '') .
                            ($item['line'] ?? '');

            $debuggingInfo .= '' . ($cnt - $i - 1) . ') ' .
                              '' . $cellFunction . ' ' .
                              '[' . $cellLocation . ']' . PHP_EOL .
                              MRCORE_LINE_DASH;
        }

        $debuggingInfo .= PHP_EOL;

        ##################################################################################

        if ('' !== $this->_pathToLog)
        {
            error_log('[' . date('Y-m-d H:i:s') . '] ' . $debuggingInfo, 3, $this->_pathToLog . 'mr_errors_' . $this->_developerLogin . date('Y-m') . '.log');
        }
        else
        {
            error_log($debuggingInfo);
        }
    }

}