<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\exceptions\UnitTestException;

require_once 'mrcore/services/EnvService.php';
require_once 'mrcore/services/InterfaceInjectableService.php';

/**
 * Класс для предназначен для сохранения отладочной информации в указанные лог файлы.
 *
 * Примеры использования:
 *   $logger = new LogService($envService, '', '');
 *   $logger->echoNotice('message', $var1, $var2, ...);
 *   $logger->echoError('message', $var1, $var2, ...);
 *   $logger->write('message', $var1, $var2, ...); -> $this->_pathToLog . 'mrlog_{Y-m-d}.log'
 *   $logger->writeTo('myLog:MyEvent', 'message', $var1, $var2, ...); -> $this->_pathToLog . 'myLog_{Y-m-d}.log'
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/services
 * @uses       $_ENV['MRCORE_UNITTEST'] OPTIONAL
 */
class LogService implements InterfaceInjectableService
{
    /**
     * Доступ к внешнему окружению.
     *
     * @var    EnvService
     */
    private EnvService $_envService;

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
     * @param      EnvService $envService
     * @param      string  $pathToLog OPTIONAL (/path/to/log/)
     * @param      string  $developerLogin OPTIONAL (может содержать только латинские буквы)
     */
    public function __construct(EnvService $envService,
                                string $pathToLog = null, string $developerLogin = null)
    {
        $this->_envService = $envService;

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
     * Добавление указанного сообщения в лог-файл системы.
     * (реальное название файла будет задано в виде mrlog_{developer_}%Y-%m-%d).
     *
     * @param      string  $message
     * @param      array  $args
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
     * @param      string  $message (Text message)
     * @param      string  $fileName (mylog, mylog:MyEvent, :MyEvent)
     * @param      array  $args
     * @throws     UnitTestException
     */
    public function writeTo(string $fileName, string $message, ...$args): void
    {
        $ips = $this->_envService->getUserIP();

        [$fileName, $event] = $this->_parseFileName($fileName, ['mrlog', '']);

        $message = '[' . gmdate('Y-m-d H:i:s') . ' UTC]' .
                   (0 === $ips['ip_real'] ? '' : ' [client: ' . $ips['string'] . '; url: ' . $this->_envService->getRequestUrl() . ']') .
                   ('' === $event ? '' : ' [' . $event . ']') . ' ' .
                   sprintf($message, $args) . PHP_EOL;

        if (!empty($_ENV['MRCORE_UNITTEST']))
        {
            require_once 'mrcore/exceptions/UnitTestException.php';

            throw new UnitTestException
            (
                __CLASS__ . '::' . __METHOD__,
                array
                (
                    'message' => $message,
                    'filePath' => $this->_pathToLog . $fileName,
                    'event' => $event,
                    'developer' => $this->_developerLogin,
                )
            );
        }

        if ('' !== $this->_pathToLog)
        {
            error_log($message, 3, $this->_pathToLog . $fileName . '_' . $this->_developerLogin . date('Y-m-d') . '.log');
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
     * @param      string  $string
     * @param      array  $default [string, string]
     * @return     array [string, string]
     */
    /*__private__*/protected function _parseFileName(string $string, array $default): array
    {
        if (preg_match('/^([a-z0-9\-_#%@&]*)(?::([a-z0-9\-_]*))?$/i', $string, $m) > 0)
        {
            return [$m[1] ?: $default[0], $m[2] ?? $default[1]];
        }

        return $default;
    }

}