<?php declare(strict_types=1);
namespace mrcore\mail;
use mrcore\lib\LogHelper;

/**
 * Класс провайдер для отложенной отправки писем через файл.
 *
 * @author  Andrey J. Nazarov
 */
class SenderToFile implements SenderInterface
{
    /**
     * Персональное имя провайдера.
     */
    private string $name;

    /**
     * Путь к директории, куда будут складываться почтовые сообщения.
     */
    private string $path;

    /**
     * Сообщение об ошибке.
     */
    private string $error = '';

    #################################### Methods #####################################

    /**
     * @param  array  $params  [senderName => string,
     *                          senderToFileDir => string]
     */
    public function __construct(array $params)
    {
        $this->name = $params['senderName'];
        $this->path = $params['senderToFileDir'];
    }

    /**
     * @inheritdoc
     */
    public function mail(string $to, string $subject, string $message, string $additional_headers = null, string $additional_parameters = null): bool
    {
        $this->error = '';

        ##################################################################################

        $sendMode = MailDebug::getMode();

        if (in_array($sendMode, [MailDebug::DEBUG, MailDebug::UNITTEST], true))
        {
            MailDebug::log(__METHOD__, __FILE__, __LINE__, $to, $subject, $message, $additional_headers);
            return true;
        }

        if (MailDebug::TEST === $sendMode)
        {
            // $subject .= ' [TOFILE-TO: ' . $to . ']';
            $to = MailDebug::getTestEmail();
        }

        ##################################################################################

        $logHelper = new LogHelper($this->path);

        return (false !== $logHelper->writeToLogFile
        (
            '--#' . LogHelper::SEPARATOR .
            base64_encode($this->name) . LogHelper::SEPARATOR .
            base64_encode($to) . LogHelper::SEPARATOR .
            base64_encode($subject) . LogHelper::SEPARATOR .
            base64_encode($additional_headers) . LogHelper::SEPARATOR .
            base64_encode($additional_parameters) . LogHelper::NEWLINE .
            "#" . LogHelper::SEPARATOR .
            chunk_split(base64_encode($message), 2048, LogHelper::NEWLINE . "#" . LogHelper::SEPARATOR) . "#--"
        ));
    }

    /**
     * @inheritdoc
     */
    public function getError(): string
    {
        return $this->error;
    }

}