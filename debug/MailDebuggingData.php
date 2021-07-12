<?php declare(strict_types=1);
namespace mrcore\debug;
use MrEnv;
use mrcore\lib\mail\ISender;

require_once 'mrcore/Constants.php';
require_once 'mrcore/MrEnv.php';
require_once 'mrcore/debug/AbstractDebuggingData.php';
require_once 'mrcore/debug/Tools.php';

/**
 * Класс форматирует информацию об ошибке и связанную с ней
 * отладочную информацию виде текста и отправляет её на email-ы разработчикам.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore.debug
 */
class MailDebuggingData extends AbstractDebuggingData
{

    ################################### Properties ###################################

    /**
     * Объект отправителя сообщений.
     *
     * @var    ISender
     */
    private ISender $_sender;

    /**
     * Email-ы разработчиков на которые отправляется
     * сформированная отладочная информация.
     *
     * @var    string
     */
    private string $_developersEmails;

    /**
     * Обратный e-mail адрес.
     *
     * @var    string
     */
    private string $_fromEmail;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      array  $senderParams [senderSource => string,
     *                                   toEmail => string,
     *                                   fromEmail => string, ...]
     */
    public function __construct(array $senderParams)
    {
        assert(isset($senderParams['senderSource']));
        assert(isset($senderParams['toEmail']));
        assert(isset($senderParams['fromEmail']));

        $senderSource = $senderParams['senderSource'];
        require_once strtr(ltrim($senderSource, '\\'), '\\', '/') . '.php';

        $sender = new $senderSource($senderParams);

        if (!($sender instanceof ISender))
        {
            trigger_error(sprintf('The created object of class %s is not an inheritor of interface %s', $senderSource, '\mrcore\lib\mail\ISender'), E_USER_ERROR);
        }

        $this->_sender = &$sender;
        $this->_developersEmails = $senderParams['toEmail'];
        $this->_fromEmail = $senderParams['fromEmail'];
    }

    /**
     * Приём информации об ошибке, форматирование её в виде текста и отправка на email-ы разработчикам.
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

        $ips = MrEnv::getUserIP();

        $debuggingInfo = ' Date: ' . date('D, d M Y H:i:s O') . PHP_EOL .
                         (0 === $ips['ip_real'] ? '' :
                             'Client: ' . $ips['string'] . '; URL: ' . MrEnv::getRequestUrl() . PHP_EOL .
                             'User Agent: ' . MrEnv::getUserAgent() . PHP_EOL .
                             'Referer URL: ' . MrEnv::getRefererUrl() . PHP_EOL) .
                         (empty($_REQUEST) ? '' : (' $_REQUEST = ' . rtrim(var_export(Tools::getHiddenData($_REQUEST, self::WORDS_TO_HIDE), true), ')') . " );\n")) .
                         ' ' . $errorMessage . PHP_EOL;

        if ('' !== $sqlQuery)
        {
            $debuggingInfo .= MRCORE_LINE_DASH . ' ' . $sqlQuery . PHP_EOL;
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

        ##################################################################################

        $subject = $this->_getSubject($errno, $errfile);

        $headers = "MIME-Version: 1.0\r\n" .
                   'From: ' . $this->_fromEmail . "\r\n" .
                   'Return-Path: ' . $this->_fromEmail;

        $this->_sender->mail($this->_developersEmails, $subject, $debuggingInfo, $headers);
    }

    /**
     * Формирование темы письма об ошибке.
     *
     * @param      int  $errno
     * @param      string $errfile
     * @return     string
     */
    private function _getSubject(int $errno, string $errfile): string
    {
        $result = sprintf('%s::%s - %s', self::getTypeError($errno), basename($errfile), date('d M H:i'));

        if ('' !== ($host = MrEnv::get('HTTP_HOST')))
        {
            $result = $host . ' ' . $result;
        }

        return $result;
    }

}