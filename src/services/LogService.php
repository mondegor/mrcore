<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\http\ClientEnvironment;
use mrcore\base\TraitSingleton;
use mrcore\exceptions\UnitTestException;

/**
 * Класс для предназначен для сохранения отладочной информации в указанные лог файлы.
 *
 * Примеры использования:
 *   $logger = new LogService($clientEnvironment, '', '');
 *   $logger->echoNotice('message', $var1, $var2, ...);
 *   $logger->echoError('message', $var1, $var2, ...);
 *   $logger->write('message', $var1, $var2, ...); -> $this->_pathToLog . 'mrlog_{Y-m-d}.log'
 *   $logger->writeTo('myLog:MyEvent', 'message', $var1, $var2, ...); -> $this->_pathToLog . 'myLog_{Y-m-d}.log'
 *
 * @author  Andrey J. Nazarov
 * @uses       $_ENV['MRCORE_UNITTEST'] OPTIONAL
 */
class LogService implements ServiceInterface
{
    use TraitSingleton;

    /**
     * Путь к лог-файлу.
     */
    private string $pathToLog = '';

    /**
     * Логин разработчика (может содержать только латинские буквы).
     */
    private string $developerLogin = '';

    #################################### Methods #####################################

    /**
     * @param  string|null     $pathToLog // /path/to/log/
     * @param  string|null     $developerLogin // может содержать только латинские буквы
     */
    public function __construct(private ?ClientEnvironment $clientEnvironment,
                                string $pathToLog = null,
                                string $developerLogin = null)
    {
        $this->_initSingleton();

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
     * Добавление указанного сообщения в лог-файл системы.
     * (реальное название файла будет задано в виде mrlog_{developer_}%Y-%m-%d).
     *
     * @param      array  $args [mixed, ...]
     * @throws     UnitTestException
     */
    public function write(string $message, ...$args): void
    {
        $this->writeTo('', $message, $args);
    }

    /**
     * Добавление указанного сообщения в лог-файл системы.
     * $fileName можно задать в виде 'string:string',
     * тогда первая строка будет названием файла, вторая - названием события.
     * (реальное название файла будет задано в виде $fileName_{developer_}%Y-%m-%d).
     *
     * Название файла может содержать следующие символы: a-z, 0-9, -, _, #, %, @, &
     * Название события может содержать следующие символы: a-z, 0-9, -, _
     *
     * @param      string  $fileName // mylog, mylog:MyEvent, :MyEvent
     * @param      string  $message // Text message
     * @param      array  $args [mixed, ...]
     * @throws     UnitTestException
     */
    public function writeTo(string $fileName, string $message, ...$args): void
    {
        [$fileName, $event] = $this->_parseFileName($fileName, ['mrlog', '']);

        $clientInfo = '';

        if (null !== $this->clientEnvironment)
        {
            $ips = $this->clientEnvironment->getRemoteIp();
            $clientInfo = ' [client: ' . $ips['string'] . '; url: ' . $this->clientEnvironment->getRequestUrl() . ']';
        }

        $message = '[' . gmdate('Y-m-d H:i:s') . ' UTC]' . $clientInfo .
                   ('' === $event ? '' : ' [' . $event . ']') . ' ' .
                   vsprintf($message, $args) . PHP_EOL;

        if (!empty($_ENV['MRCORE_UNITTEST']))
        {
            throw new UnitTestException
            (
                __METHOD__,
                array
                (
                    'message' => $message,
                    'filePath' => $this->pathToLog . $fileName,
                    'event' => $event,
                    'developer' => $this->developerLogin,
                )
            );
        }

        if ('' !== $this->pathToLog)
        {
            error_log($message, 3, $this->pathToLog . $fileName . '_' . $this->developerLogin . date('Y-m-d') . '.log');
        }
        else
        {
            error_log($message);
        }
    }

    /**
     * $fileName можно задать в виде 'string', 'string:string', ':string'
     * Название файла может содержать следующие символы: a-z, A-Z, 0-9, -, _, #, %, @, &
     * Название события может содержать следующие символы: a-z, A-Z, 0-9, -, _
     *
     * @param      array  $default [string, string]
     * @return     array [string, string]
     */
    protected function _parseFileName(string $string, array $default): array
    {
        if (preg_match('/^([a-z0-9\-_#%@&]*)(?::([a-z0-9\-_]*))?$/i', $string, $m) > 0)
        {
            return [$m[1] ?: $default[0], $m[2] ?? $default[1]];
        }

        return $default;
    }

}