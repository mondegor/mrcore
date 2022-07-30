<?php declare(strict_types=1);
namespace mrcore\debug;
use mrcore\console\EnumLiteral;
use mrcore\mail\SenderInterface;

/**
 * Класс форматирует информацию об ошибке и связанную с ней
 * отладочную информацию виде текста и отправляет её на email-ы разработчикам.
 *
 * @author  Andrey J. Nazarov
 */
class MailDebuggingData extends AbstractDebuggingData
{
    /**
     * Объект отправителя сообщений.
     */
    private SenderInterface $sender;

    /**
     * Обратный e-mail адрес.
     */
    private string $fromEmail;

    #################################### Methods #####################################

    /**
     * @param  array  $senderParams [senderClass => string,
     *                               fromEmail => string, ...]
     */
    public function __construct(private HelperClientData $clientData,
                                array $senderParams,
                                private string $developersEmails)
    {
        assert(isset($senderParams['senderClass']));
        assert(isset($senderParams['fromEmail']));

        $senderClass = $senderParams['senderClass'];
        $this->sender = new $senderClass($senderParams); // :TODO: вынести зависимось наружу
        $this->fromEmail = $senderParams['fromEmail'];
    }

    /**
     * Приём информации об ошибке, форматирование её в виде текста и отправка на email-ы разработчикам.
     * @inheritdoc
     */
    public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void
    {
        [$errstr, $extendedInfo] = $this->_parseError($errstr);
        $errorMessage = sprintf('%s: "%s" in %s on line %s', $this->_getTypeError($errno), $errstr, $errfile, $errline);

        $debuggingInfo = ' Date: ' . date('D, d M Y H:i:s O') . PHP_EOL .
                         $this->clientData->getInfo() .
                         ' ' . $errorMessage . PHP_EOL;

        if ('' !== $extendedInfo)
        {
            $debuggingInfo .= EnumLiteral::LINE_DASH . '  ' . $extendedInfo . PHP_EOL;
        }

        $debuggingInfo .= ' ' . $errorMessage . PHP_EOL;

        if ('' !== $extendedInfo)
        {
            $debuggingInfo .= EnumLiteral::LINE_DASH . ' ' . $extendedInfo . PHP_EOL;
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

        ##################################################################################

        $subject = $this->_getSubject($errno, $errfile);

        $headers = "MIME-Version: 1.0\r\n" .
                   'From: ' . $this->fromEmail . "\r\n" .
                   'Return-Path: ' . $this->fromEmail;

        $this->sender->mail($this->developersEmails, $subject, $debuggingInfo, $headers);
    }

    ##################################################################################

    /**
     * Формирование темы письма об ошибке.
     */
    protected function _getSubject(int $errno, string $errfile): string
    {
        $result = sprintf('%s::%s - %s', $this->_getTypeError($errno), basename($errfile), date('d M H:i'));

        if ('' !== ($host = $this->clientData->getHostname()))
        {
            $result = $host . ' ' . $result;
        }

        return $result;
    }

}