<?php declare(strict_types=1);
namespace mrcore\mail;

/**
 * Класс провайдер для отправки писем через smtp.
 *
 * @author  Andrey J. Nazarov
 */
class SenderSmtp implements SenderInterface
{
    /**
     * Класс отправки сообщений по smtp протоколу.
     */
    private SmtpMail $smtpMail;

    /**
     * Сообщение об ошибке.
     */
    private string $error = '';

    #################################### Methods #####################################

    /**
     * @param  array  $params  [fromName => string,
     *                          fromEmail => string,
     *                          fromHost => string,
     *                          smtpHost => string,
     *                          smtpPort => int,
     *                          smtpExtHello => bool,
     *                          smtpUserName => string,
     *                          smtpPassword => string,
     *                          smtpHeaders => string[] OPTIONAL,
     *                          smtpTimeout => int OPTIONAL]
     */
    public function __construct(array $params)
    {
        $this->smtpMail = new SmtpMail
        (
            $params['smtpUserName'],
            $params['smtpPassword'],
            $params['smtpHost'],
            $params['smtpPort'],
            $params['fromHost'],
            Format::combineEmail($params['fromName'], $params['fromEmail']),
            $params['smtpHeaders'] ?? null,
            $params['smtpExtHello'],
            $params['smtpTimeout'] ?? null
        );
    }

    /**
     * Установка данных для авторизации на почтовом сервере.
     */
    public function setAuth(string $userName, string $password = null): void
    {
        $this->smtpMail->setAuth($userName, $password);
    }

    /**
     * @inheritdoc
     */
    public function mail(string $to, string $subject, string $message, string $additional_headers = null, string $additional_parameters = null): bool
    {
        $this->error = '';

        if ($this->smtpMail->send($to, $subject, $message, $additional_headers, $additional_parameters))
        {
            return true;
        }

        $this->error = $this->smtpMail->getError();

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getError(): string
    {
        return $this->error;
    }

}