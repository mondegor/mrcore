<?php declare(strict_types=1);
use const mrcore\console\DCOLOR_DARK_YELLOW_BLACK;
use const mrcore\console\DCOLOR_GREEN_BLACK;
use const mrcore\console\DCOLOR_LIGHT_BLUE_BLACK;
use const mrcore\console\DCOLOR_RED_BLACK;
use mrcore\console\AbstractPainter;
use mrcore\exceptions\UnitTestException;

require_once 'mrcore/MrEnv.php';
// require_once 'mrcore/console/AbstractPainter.php';

/**
 * Класс для предназначен для вывода сообщений на экран,
 * а также для сохранения отладочной информации в указанные лог файлы.
 *
 * Примеры использования:
 *   MrLogger::echoNotice('message', $var1, $var2, ...);
 *   MrLogger::echoError('message', $var1, $var2, ...);
 *   MrLogger::write('message', $var1, $var2, ...); -> self::$_pathToLog . 'mrlog_{Y-m-d}.log'
 *   MrLogger::writeTo('myLog:MyEvent', 'message', $var1, $var2, ...); -> self::$_pathToLog . 'myLog_{Y-m-d}.log'
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore
 * @uses       $_ENV['MRCORE_UNITTEST'] OPTIONAL
 */
/*__class_static__*/ final class MrLogger
{

    ################################### Properties ###################################

    /**
     * Имя лог-файла куда будут сохранятся сообщения об ошибках.
     *
     * @var    string
     */
    private static string $_pathToLog = '';

    /**
     * Логин разработчика (может содержать только латинские буквы).
     *
     * @var    string
     */
    private static string $_developerLogin = '';

    /**
     * Логин разработчика (может содержать только латинские буквы).
     *
     * @var    AbstractPainter
     */
    private static ?AbstractPainter $_painter = null;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      string  $pathToLog OPTIONAL (/path/to/log/)
     * @param      string  $developerLogin OPTIONAL (может содержать только латинские буквы)
     * @param      AbstractPainter $painter OPTIONAL
     */
    public static function init(string $pathToLog = null, string $developerLogin = null,
                                AbstractPainter $painter = null): void
    {
        if (null !== $pathToLog)
        {
            self::$_pathToLog = $pathToLog;
        }

        if (null !== $developerLogin && ctype_alpha($developerLogin))
        {
            self::$_developerLogin = $developerLogin . '_';
        }

        self::$_painter = $painter;
    }

    /**
     * Вывод указанного сообщения на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoMessage(string $message, ...$args): void
    {
        self::_echo($message, $args, 0);
    }

    /**
     * Вывод указанного уведомления на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoNotice(string $message, ...$args): void
    {
        self::_echo($message, $args, DCOLOR_LIGHT_BLUE_BLACK);
    }

    /**
     * Вывод указанного предупреждения на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoWarning(string $message, ...$args): void
    {
        self::_echo($message, $args, DCOLOR_DARK_YELLOW_BLACK);
    }

    /**
     * Вывод указанной ошибки на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoError(string $message, ...$args): void
    {
        self::_echo($message, $args, DCOLOR_RED_BLACK);
    }

    /**
     * Вывод указанного сообщения об успехе на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoSuccess(string $message, ...$args): void
    {
        self::_echo($message, $args, DCOLOR_GREEN_BLACK);
    }

    /**
     * Обёртывание указанного сообщения указанным цветом.
     *
     * @param      string  $message
     * @param      int     $doubleColor
     * @return     string
     */
    public static function wrapColor(string $message, int $doubleColor): string
    {
        if (null === self::$_painter)
        {
            return $message;
        }

        return self::$_painter->coloring($message, $doubleColor);
    }

    /**
     * Добавление указанного сообщения в лог-файл системы.
     * (реальное название файла будет задано в виде mrlog_{developer_}%Y-%m-%d).
     *
     * @param      string  $message
     * @param      array  $args
     * @throws     UnitTestException
     */
    public static function write(string $message, ...$args): void
    {
        self::writeTo('', $message, $args);
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
    public static function writeTo(string $fileName, string $message, ...$args): void
    {
        $ips = MrEnv::getUserIP();

        [$fileName, $event] = self::_parseFileName($fileName, ['mrlog', '']);

        $message = '[' . gmdate('Y-m-d H:i:s') . ' UTC]' .
                   (0 === $ips['ip_real'] ? '' : ' [client: ' . $ips['string'] . '; url: ' . MrEnv::getRequestUrl() . ']') .
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
                    'filePath' => self::$_pathToLog . $fileName,
                    'event' => $event,
                    'developer' => self::$_developerLogin,
                )
            );
        }

        if ('' !== self::$_pathToLog)
        {
            error_log($message, 3, self::$_pathToLog . $fileName . '_' . self::$_developerLogin . date('Y-m-d') . '.log');
        }
        else
        {
            error_log($message);
        }
    }

    /**
     * Вывод указанного сообщения на экран.
     * Если в классе инициализировано использование цвета,
     * то к сообщению этот цвет применяется.
     *
     * @param      string  $message
     * @param      array  $args
     * @param      int  $doubleColor
     */
    private static function _echo(string $message, array $args, int $doubleColor): void
    {
        if (0 === ($cmp = strncmp($message, '!', 1)))
        {
            $message = substr($message, 1);
        }

        if ($doubleColor > 0 && null !== self::$_painter)
        {
            $message = self::$_painter->coloring($message, $doubleColor);
        }

        vprintf($message . (0 === $cmp ? '' : "\n"), $args);
    }

    /**
     * $fileName можно задать в виде 'string', 'string:string', ':string'
     * Название файла может содержать следующие символы: a-z, 0-9, -, _, #, %, @, &
     * Название события может содержать следующие символы: a-z, 0-9, -, _
     *
     * @param      string  $string
     * @param      array  $default
     * @return     array [string, string]
     */
    private static function _parseFileName(string $string, array $default): array
    {
        if (preg_match('/^([a-z0-9\-_#%@&]*)(?::([a-z0-9\-_]+))?$/i', $string, $m) > 0)
        {
            return [$m[1] ?: $default[0], $m[2] ?? $default[1]];
        }

        return $default;
    }

}