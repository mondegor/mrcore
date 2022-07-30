<?php declare(strict_types=1);
namespace mrcore\mail;
use mrcore\exceptions\UnitTestException;

/**
 * Вспомогательный класс для отладки писем.
 *
 * @author  Andrey J. Nazarov
 * @uses       $_ENV['MRAPP_DEBUG']
 * @uses       $_ENV['MRAPP_DEVELOPER']
 * @uses       $_ENV['MRAPP_DIR_LOGS']
 * @uses       $_ENV['MRCORE_UNITTEST'] OPTIONAL
 * @uses       $_ENV['MRCORE_DBG_EMAIL'] OPTIONAL
 */
/*__class_static__*/ class MailDebug
{
    /**
     * Режимы работы отправки писем.
     */
    public const PROD = 0,
                 DEBUG = 1,
                 TEST = 2,
                 UNITTEST = 3;

    /**
     * Возвращается текущий режим работы почтового сервиса.
     */
    public static function getMode(): int
    {
        if (!empty($_ENV['MRCORE_UNITTEST']))
        {
            return self::UNITTEST;
        }

        if (!$_ENV['MRAPP_DEBUG'])
        {
            return self::PROD;
        }

        if (empty($_ENV['MRCORE_DBG_EMAIL']))
        {
            return self::DEBUG;
        }

        return self::TEST;
    }

    /**
     * Возвращается тестовый емаил, который заменяет собой
     * реальный в целях проверки отправки письма и его отображения в почтовом клиенте.
     */
    public static function getTestEmail(): string
    {
        return $_ENV['MRCORE_DBG_EMAIL'] ?? '';
    }

    /**
     * Логирование исходника письма для отладки.
     *
     * @throws     UnitTestException
     */
    public static function log(string $classMethod, string $file, int $line, string $to, string $subject, string $message, string $headers = null): void
    {
        if (self::UNITTEST === self::getMode())
        {
            throw new UnitTestException
            (
                $classMethod,
                array
                (
                    'to' => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'headers' => $headers,
                )
            );
        }

        ##################################################################################

        $content = '#SENDER: ' . substr(strrchr(str_replace('::', '->', $classMethod), '\\'), 1) . '() [' . $file . ':' . $line . "]\n" .
                   '#TO: ' . $to . "\n" .
                   '#SUBJECT: ' . $subject . "\n" .
                   '#HEADERS:' . (null === $headers ? ' NULL' : "\n  " . str_replace("\r\n", "\r\n  ", trim($headers))) . "\n" .
                   '#MESSAGE:' . "\n" . trim($message) . "\n\n" .
                   '##################################################################################' . "\n\n";

        $developerLogin = ctype_alpha($_ENV['MRAPP_DEVELOPER']) ? $_ENV['MRAPP_DEVELOPER'] . '_' : '';

        if ('' !== $_ENV['MRAPP_DIR_LOGS'])
        {
            error_log($content, 3, $_ENV['MRAPP_DIR_LOGS'] . 'mr_sendmail_' . $developerLogin . date('Y-m-d') . '.log');
        }
        else
        {
            error_log($content);
        }
    }

}