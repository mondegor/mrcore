<?php declare(strict_types=1);
namespace mrcore\mail;

/**
 * Класс для отправки писем через сокет по протоколу smtp.
 *
 * <code>
 * $senderParams = $GLOBALS[MRCORE_G_SENDER]['mrcore.localhost'];
 *
 * $mail = new SmtpMail($senderParams['smtpUserName'],
 *                      $senderParams['smtpPassword'],
 *                      $senderParams['smtpHost'],
 *                      $senderParams['smtpPort'],
 *                      $senderParams['fromHost'],
 *                      Format::combineEmail($params['fromName'], $params['fromEmail']),
 *                      $params['smtpHeaders'] ?? null,
 *                      $senderParams['smtpExtHello'],
 *                      $params['smtpTimeout'] ?? null);
 *
 * if (!$mail->send($to, $subject, $message, $headers))
 * {
 *     echo $mail->getError();
 * }
 * </code>
 *
 * @author  Andrey J. Nazarov
 */
class SmtpMail
{
    /**
     * Таймаут соединения c smtp сервером.
     */
    public int $timeout = 5;

    /**
     * Логин (e-mail) для авторизации по smtp.
     */
    private string $userName;

    /**
     * Пароль для авторизации по smtp.
     */
    private string $password;

    /**
     * Хост smtp сервера.
     */
    private string $host;

    /**
     * Порт smtp сервера.
     */
    private int $port;

    /**
     * Сервер с которого отправляется почта (домен или IP).
     */
    private string $fromHost;

    /**
     * Сервер с которого отправляется почта (домен или IP).
     *
     * @var    array [string, string] // sample: [name, email]
     */
    private array $fromEmail;

    /**
     * Дополнительные заголовки письма.
     */
    private Headers $headers;

    /**
     * Требуется ли расширенное приветствие для smtp сервера.
     */
    private bool $extHello = false;

    /**
     * Сообщение об ошибке.
     */
    private string $error = '';

    #################################### Methods #####################################

    public function __construct(string $userName, string $password, string $host, int $port, string $fromHost,
                                string $fromEmail = null, array $headers = null, bool $extHello = null, int $timeout = null)
    {
        $this->userName = $userName;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->fromHost = $fromHost;
        $this->fromEmail = $this->_explodeEmail($fromEmail ?? $userName);
        $this->headers = new Headers($headers);

        if (null !== $extHello)
        {
            $this->extHello = $extHello;
        }

        if (null !== $timeout)
        {
            $this->timeout = $timeout;
        }
    }

    /**
     * Установка данных для авторизации на почтовом сервере.
     */
    public function setAuth(string $userName, string $password = null): void
    {
        // :WARNING: если userName и fromEmail совпадают, то он также поменяется
        if ($this->userName === $this->fromEmail[1])
        {
            $this->fromEmail[1] = $userName;
        }

        $this->userName = $userName;

        if (null !== $password)
        {
            $this->password = $password;
        }
    }

    /**
     * Отправка письма получателю.
     */
    public function send(string $to, string $subject, string $message, string $headers = null, string $params = null): bool
    {
        $this->error = '';

        $_headers = clone $this->headers;

        if (null !== $headers)
        {
            $_headers->setHeaders(explode("\r\n", $headers));
        }

        // подстановка в заголовок Return-Path своего значения
        $_headers->setHeader('Return-Path', $this->fromEmail[1]);

        ##################################################################################

        $sendMode = MailDebug::getMode();

        if (in_array($sendMode, [MailDebug::DEBUG, MailDebug::UNITTEST], true))
        {
            MailDebug::log(__METHOD__, __FILE__, __LINE__, $to, $subject, $message, $_headers->toString());
            return true;
        }

        if (MailDebug::TEST === $sendMode)
        {
            // $subject .= ' [SMTP-TO: ' . $to . ']';
            $to = MailDebug::getTestEmail();
        }

        ##################################################################################

        $content  = 'Subject: ' . trim($subject) . "\r\n" .
                    'To: ' . $this->_formatEmail($to) . "\r\n" .
                    'Date: ' . date('D, d M Y H:i:s') . " UT\r\n" .
                    $_headers->toString() . "\r\n" .
                    trim($message) . "\r\n";

        return $this->_send($to, $content);
    }

    /**
     * Отправка письма получателю посредством сокета.
     */
    protected function _send(string $to, string $content): bool
    {
        if (!($socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout)))
        {
            $this->error = '[smtp] Error - no: ' . $errno . '; str: ' . $errstr;
            return false;
        }

        $tmpbuf = '';

        if (!$this->_parseServerResponse($socket, '220', $tmpbuf))
        {
            $this->error = '[smtp] Error - 220 (' . $tmpbuf . ')';
            return false;
        }

        ##################################################################################

        $result = false;

        do
        {
            $this->_putCmd($socket, 'HELO ' . $this->fromHost); // приветствие

            if (!$this->_parseServerResponse($socket, '250', $tmpbuf))
            {
                $this->error = '[smtp] Error of command sending: HELO (' . $tmpbuf . ')';
                break;
            }

            if ($this->extHello) // расширенное приветствие
            {
                $this->_putCmd($socket, 'EHLO ' . $this->fromHost);

                if (!$this->_parseServerResponse($socket, '250', $tmpbuf))
                {
                    $this->error = '[smtp] Error of command sending: EHLO (' . $tmpbuf . ')';
                    break;
                }
            }

            $this->_putCmd($socket, 'AUTH LOGIN');

            if (!$this->_parseServerResponse($socket, '334', $tmpbuf))
            {
                $this->error = '[smtp] Autorization error - 334 (' . $tmpbuf . ')';
                break;
            }

            $this->_putCmd($socket, base64_encode($this->userName));

            if (!$this->_parseServerResponse($socket, '334', $tmpbuf))
            {
                $this->error = '[smtp] Autorization username error - 334 (' . $tmpbuf . ')';
                break;
            }

            $this->_putCmd($socket, base64_encode($this->password));

            if (!$this->_parseServerResponse($socket, '235', $tmpbuf))
            {
                $this->error = '[smtp] Autorization password error - 235 (' . $tmpbuf . ')';
                break;
            }

            // :TODO: проверить, можно ли использовать расширенный формат:
            //        Format::combineEmail($this->fromEmail[0], $this->fromEmail[1])
            $this->_putCmd($socket, 'MAIL FROM: <' . $this->fromEmail[1] . '>');

            if (!$this->_parseServerResponse($socket, '250', $tmpbuf))
            {
                $this->error = '[smtp] Error of command sending: MAIL FROM (' . $tmpbuf . ')';
                break;
            }

            foreach (explode(',', $this->_stripEmails($to)) as $_to)
            {
                $this->_putCmd($socket, 'RCPT TO: <' . trim($_to) . '>');

                if (!$this->_parseServerResponse($socket, '250', $tmpbuf))
                {
                    $this->error = '[smtp] Error of command sending: RCPT TO (' . $tmpbuf . ')';
                    break;
                }
            }

            $this->_putCmd($socket, 'DATA');

            if (!$this->_parseServerResponse($socket, '354', $tmpbuf))
            {
                $this->error = '[smtp] Error of command sending: DATA (' . $tmpbuf . ')';
                break;
            }

            $this->_putCmd($socket, $content . "\r\n.");

            if (!$this->_parseServerResponse($socket, '250', $tmpbuf))
            {
                $this->error = '[smtp] E-mail didn\'t sent - 250 (' . $tmpbuf . ')';
                break;
            }

            $this->_putCmd($socket, 'QUIT');

            $result = true;
        }
        while (false);

        fclose($socket);

        return $result;
    }

    /**
     * Возвращается ошибка, если письмо не было отправлено.
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Отправка команды на удалённый сервер через socket.
     */
    protected function _putCmd($socket, string $cmd): void
    {
        // echo $cmd;
        fwrite($socket, $cmd . "\r\n");
    }

    /**
     * Разбор очередной строки полученной с удалённого сервера.
     */
    protected function _parseServerResponse($socket, string $response, string &$tmpbuf): bool
    {
        do
        {
            if (false === ($buff = fgets($socket, 256)))
            {
                return false;
            }
        }
        while (' ' !== substr($buff, 3, 1));

        if (!str_starts_with($buff, $response))
        {
            $tmpbuf = $buff;
            return false;
        }

        return true;
    }

    ##################################################################################

    protected function _explodeEmail(string $email): array
    {
        return Format::explodeEmail($email);
    }

    protected function _formatEmail(string $email): string
    {
        return Format::formatEmail($email);
    }

    protected function _stripEmails(string $emails): string
    {
        return Format::stripEmails($emails);
    }

}