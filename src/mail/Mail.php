<?php declare(strict_types=1);
namespace mrcore\mail;

/**
 * Класс для формирования писем в виде plain, html, прикрепления к ним файлов и отправки.
 * Может отправлять письма сам, а может использоваться в качестве адаптера.
 *
 * <code>
 * $mail = new Mail('ivanov.ivan@mrcore.localhost');
 * $mail->setSender($senderClass, $senderParams);
 * $mail->setTo('Petrov Petr <petrov.petr@mrcore.localhost>');
 * $mail->setFrom('Ivanov Ivan <ivanov.ivan@mrcore.localhost>');
 * $mail->setReturnEmail('ivanov.ivan@mrcore.localhost');
 * $mail->charset = Mail::CHARSET_WINDOWS_UTF_8;
 * $mail->contentType = Mail::CONTENT_TYPE_HTML;
 * $mail->subject = 'SPAM from Mrcore';
 * $mail->body = "SPAM\n\nMrcore. Click here: <a href="http://mrcore.localhost/">http://mrcore.localhost/</a>";
 * $mail->getAttachments()->setAttachment('image.gif', '/home/mrcore/i/image.gif')
 * </code>
 *
 * @author  Andrey J. Nazarov
 */
class Mail
{
    /**
     * Константы типа контента сообщения.
     */
    public const CONTENT_TYPE_TEXT      = 'text/plain',
                 CONTENT_TYPE_HTML      = 'text/html',
                 CONTENT_TYPE_TEXT_HTML = 'multipart/alternative';

    /**
     * Константы кодировок письма.
     */
    public const CHARSET_UTF_8        = 'UTF-8',
                 CHARSET_ISO_8859_1   = 'iso-8859-1',
                 CHARSET_WINDOWS_1251 = 'windows-1251';

    ################################### Properties ###################################

    /**
     * Mail body charset (default: UTF-8).
     */
    public string $charset = self::CHARSET_UTF_8;

    /**
     * Тип отправляемого письма.
     */
    public string $contentType = self::CONTENT_TYPE_TEXT;

    /**
     * Заголовок письма.
     */
    public string $subject = '';

    /**
     * Содержание письма.
     */
    public string $body = '';

    /**
     * Флаг использования расширенных emails:
     * Пример: Ivanov Ivan <ivanov.ivan@mrcore.localhost>,
     * в случае оключения флага при отправки письма
     * будет подставлено только: ivanov.ivan@mrcore.localhost.
     * По умолчанию включено.
     */
    public bool $isUseExtendEmails = true;

    /**
     * Флаг использования обратного e-mail при отправке письма.
     * Этот e-mail добавляется 5-ым параметром в функцию mail.
     * По умолчанию включено.
     */
    public bool $isUseReturnEmail = true;

    /**
     * Список emails разделённых через запятую,
     * которым отправляется письмо.
     */
    private string $to;

    /**
     * E-mail отправителя письма.
     */
    private string $from = '';

    /**
     * Additional recipients emails (CC field).
     */
    private string $cc = '';

    /**
     * Адрес который будет автоматически подставляться
     * при ответе адресатом на письмо.
     */
    private string $replyTo = '';

    /**
     * Обратный адрес отправляемого письма.
     */
    private string $returnEmail = '';

    /**
     * Контейнер заголовков письма.
     */
    private Headers $headers;

    /**
     * Прикреплённые файлы к письму.
     *
     * @var    Attachments|null
     */
    private ?Attachments $attachments = null;

    /**
     * Сообщение об ошибке.
     */
    private string $error = '';

    /**
     * Объект отправителя сообщений.
     */
    private ?SenderInterface $sender = null;

    #################################### Methods #####################################

    /**
     * @param  string       $to  // recipients emails (comma separated)
     * @param  string|null  $from  // sender's email
     */
    public function __construct(string $to, string $from = null, string $subject = null, string $returnEmail = null)
    {
        $this->setTo($to);

        if (null !== $from)
        {
            $this->setFrom($from);
        }

        if (null !== $subject)
        {
            $this->subject = $subject;
        }

        if (null !== $returnEmail)
        {
            $this->setReturnEmail($returnEmail);
        }

        $this->headers = new Headers();
    }

    /**
     * Установка оправителя сообщений.
     *
     * @param      array  $params [string => mixed, ...]
     */
    public function setSender(string $class, array $params): void
    {
        $this->sender = new $class($params);
    }

    /**
     * Возвращается отправитель сообщений.
     */
    public function getSender(): ?SenderInterface
    {
        return $this->sender;
    }

    /**
     * Установка получателя письма.
     *
     * @param  bool  $cc Additional recipients emails (CC field)
     */
    public function setTo(string $to, bool $cc = false): void
    {
        if (!$this->_checkEmail($to))
        {
            trigger_error(sprintf('Email "%s" не соответствует формату электронного почтового адреса', $to), E_USER_NOTICE);
            return;
        }

        if ($cc)
        {
            $this->cc = $to;
            return;
        }

        $this->to = $to;
    }

    /**
     * Возвращается получатель письма.
    */
    public function getTo(bool $cc = false): string
    {
        return $cc ? $this->cc : $this->to;
    }

    /**
     * Установка отправителя письма.
     */
    public function setFrom(string $from, bool $reply = false): void
    {
        if (!$this->_checkEmail($from))
        {
            trigger_error(sprintf('Email "%s" не соответствует формату электронного почтового адреса', $from), E_USER_NOTICE);
            return;
        }

        if ($reply)
        {
            $this->replyTo = $from;
            return;
        }

        $this->from = $from;

        // если ещё отвечатель не задан, то подставляется по умолчанию отправитель
        if ('' === $this->replyTo && 0 !== strncmp($from, 'no-reply@', 9))
        {
            $this->replyTo = $from;
        }
    }

    /**
     * Возвращается отправитель письма.
     */
    public function getFrom(bool $reply = false): string
    {
        return $reply ? $this->replyTo : $this->from;
    }

    /**
     * Установка обратного адреса письма.
     */
    public function setReturnEmail(string $returnEmail): void
    {
        $returnEmail = $this->_stripEmail($returnEmail);

        if (!$this->_checkEmail($returnEmail))
        {
            trigger_error(sprintf('Email "%s" не соответствует формату электронного почтового адреса', $returnEmail), E_USER_NOTICE);
            return;
        }

        $this->returnEmail = $returnEmail;
    }

    /**
     * Возвращается обратный адрес письма.
     */
    public function getReturnEmail(): string
    {
        return $this->returnEmail;
    }

    /**
     * Возвращается контейнер файлов письма.
     */
    public function getAttachments(): Attachments
    {
        if (null === $this->attachments)
        {
            $this->attachments = new Attachments($this);
        }

        return $this->attachments;
    }

    /**
     * Отправка письма получателю.
     */
    public function send(): bool
    {
        $this->error = '';

        // поиск какого нибудь подходящего обратного адреса
        $returnEmail = ('' === $this->returnEmail ? $this->_stripEmail($this->from) : $this->returnEmail);

        $_headers = clone $this->headers;

        // если значение у какого либо заголовка окажется пустым, то этот заголовок не будет сформирован
        $_headers->setHeader('MIME-Version', '1.0')
                 ->setHeader('From', $this->isUseExtendEmails ? $this->_formatEmail($this->from) : $this->_stripEmail($this->from))
                 ->setHeader('Reply-To', $this->isUseExtendEmails ? $this->_formatEmail($this->replyTo) : $this->_stripEmail($this->replyTo))
                 ->setHeader('cc', $this->_stripEmails($this->cc))
                 ->setHeader('Return-Path', $returnEmail);

        ##################################################################################

        $sendMode = MailDebug::getMode();
        $debugMode = in_array($sendMode, [MailDebug::DEBUG, MailDebug::UNITTEST], true);
        $testMode = (MailDebug::TEST === $sendMode);

        ##################################################################################

        $contentType = $this->contentType;

        if (self::CONTENT_TYPE_TEXT_HTML === $contentType)
        {
            // если HTML письмо не содержит тегов, то оно отправляется как обычное
            if (0 === strcmp(strip_tags($this->body), $this->body))
            {
                $contentType = self::CONTENT_TYPE_TEXT;
            }
        }

        if (self::CONTENT_TYPE_TEXT_HTML === $contentType)
        {
            $boundary = 'ALT-' . md5(microtime(true));
            $_headers->setHeader('Content-Type', $contentType . ';' . "\r\n\t" . 'boundary="' . $boundary . '"');

            $body = '--' . $boundary . "\r\n" .
                    'Content-Type: text/plain; charset="' . $this->charset . "\"\r\n" .
                    'Content-Transfer-Encoding: base64' . "\r\n\r\n" .
                    ($debugMode ? strip_tags($this->body) : chunk_split(base64_encode(strip_tags($this->body)))) . "\r\n" .
                    '--' . $boundary . "\r\n" .
                    'Content-Type: text/html; charset="' . $this->charset . "\"\r\n" .
                    'Content-Transfer-Encoding: base64' . "\r\n\r\n" .
                    ($debugMode ? $this->body : chunk_split(base64_encode($this->body))) . "\r\n" .
                    '--' . $boundary . "--";
        }
        else
        {
            $_headers->setHeader('Content-Type', $contentType . '; charset="' . $this->charset . "\"");
            $body = ($debugMode ? $this->body : chunk_split(base64_encode($this->body)));
        }

        ##################################################################################

        if (null !== $this->attachments && ($attachments = $this->attachments->toString()))
        {
            $boundary = $this->attachments->getBoundary();
            $header = 'Content-Type: ' . $_headers->getHeader('Content-Type');
            $_headers->setHeader('Content-Type', 'multipart/mixed;' . "\r\n\t" . 'boundary="' . $boundary . '"');

            if (self::CONTENT_TYPE_TEXT_HTML === $contentType)
            {
                $body = '--' . $boundary . "\r\n" .
                        $header . "\r\n" .
                        $body . "\r\n" .
                        $attachments;
            }
            else
            {
                $body = '--' . $boundary . "\r\n" .
                        $header . "\r\n" .
                        'Content-Transfer-Encoding: base64' . "\r\n\r\n" .
                        $body . "\r\n" .
                        $attachments;
            }
        }
        else if (self::CONTENT_TYPE_TEXT_HTML !== $contentType)
        {
            $_headers->setHeader('Content-Transfer-Encoding', 'base64');
        }

        ##################################################################################

        // :WARNING: параметр $this->_to экранировать не нужно!
        $to = ($this->isUseExtendEmails ? $this->to : $this->_stripEmails($this->to));
        $subject = $this->subject;
        $headers = $_headers->toString();
        $params  = $this->isUseReturnEmail ? '-f' . $returnEmail : '';

        ##################################################################################

        if (null !== $this->sender)
        {
            if (!$debugMode)
            {
                $subject = $this->_encode($subject, $this->charset);
            }

            // :WARNING: источник сам должен обеспечивать тестовый режим
            if ($this->sender->mail($to, $subject, $body, $headers, $params))
            {
                return true;
            }

            $this->error = $this->sender->getError();
            return false;
        }

        ##################################################################################

        if ($debugMode)
        {
            MailDebug::log(__METHOD__, __FILE__, __LINE__, $to, $subject, $body, $headers);
            return true;
        }

        ##################################################################################

        if ($testMode)
        {
            $subject .= ' [MAIL-TO: ' . $to . ']';
            $to = MailDebug::getTestEmail();
        }

        if (mail($to, $this->_encode($subject, $this->charset), $body, $headers, $params))
        {
            return true;
        }

        $this->error = 'Объекту класса ' . get_class($this) . ' не удалось отправить сообщение "' . $this->subject . '" адресату: ' . $this->to;
        return false;
    }

    /**
     * Возвращается ошибка отправки письма.
     */
    public function getError(): string
    {
        return $this->error;
    }

    ##################################################################################

    protected function _formatEmail(string $email): string
    {
        return Format::formatEmail($email);
    }

    protected function _encode(string $value, string $charset): string
    {
        return Format::encode($value, $charset);
    }

    protected function _checkEmail(string $email): bool
    {
        return Format::checkEmail($email);
    }

    protected function _stripEmail(string $email): string
    {
        return Format::stripEmail($email);
    }

    protected function _stripEmails(string $emails): string
    {
        return Format::stripEmails($emails);
    }

}