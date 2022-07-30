<?php declare(strict_types=1);
namespace mrcore\console;

/**
 * Вспомагательный класс для работы в консольном режиме.
 * Также может принимать символы с клавиатуры пользователя.
 *
 * @author  Andrey J. Nazarov
 * @uses       DIRECTORY_SEPARATOR
 * @uses       PHP_VERSION
 * @uses       STDIN
 * @uses       STDOUT
 * @uses       $_SERVER['argv']
 * @uses       $_SERVER['argc']
 */
class Console
{
    /**
     * Типы завершения приложения.
     */
    public const EXIT_FAILURE = 1,
                 EXIT_SUCCESS = 0,
                 EXIT_EXCEPTION = 2;

    #################################### Methods #####################################

    /**
     * Скрипт запущен в консоле?
     */
    public function isCli(): bool
    {
        return (defined('STDIN')/*'cli' === PHP_SAPI*/);
    }

    /**
     * Скрипт запущен из под WINDOWS?
     */
    public function isWindows(): bool
    {
        return ('\\' === DIRECTORY_SEPARATOR);
    }

    /**
     * Пользователь, из под которого был запущен скрипт.
     */
    public function getUser(): string
    {
        $user = posix_getpwuid(posix_getuid());

        return $user['name'];
    }

    /**
     * Текущая версия PHP.
     *
     * @return     array [int, int] // [major, minor]
     */
    public function phpVersion(): array
    {
        $v = explode('.', PHP_VERSION);

        return [isset($v[0]) ? (int)$v[0] : 0,
                isset($v[1]) ? (int)$v[1] : 0];
    }

    /**
     * Возвращаются аргументы переданные скрипту из вне.
     *
     * @return     string[]
     */
    public function getArgs(): array
    {
        if (!isset($_SERVER['argv'], $_SERVER['argc']))
        {
            return [];
        }

        return $_SERVER['argv'];
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     *
     * Normally, we want to use a resource as a parameter, yet sadly it's not always awailable,
     * eg when running code in interactive console (`php -a`), STDIN/STDOUT/STDERR constants are not defined.
     *
     * @param resource $fileDescriptor
     * @return bool
     */
    public function isInteractive($fileDescriptor): bool
    {
        if (is_resource($fileDescriptor))
        {
            // these functions require a descriptor that is a real resource, not a numeric ID of it
            if (function_exists('stream_isatty') && stream_isatty($fileDescriptor))
            {
                return true;
            }

            // check if formatted mode is S_IFCHR
            if (function_exists('fstat'))
            {
                $stat = fstat(STDOUT);

                return isset($stat['mode']) && (020000 === ($stat['mode'] & 0170000));
            }

            return false;
        }

        return function_exists('posix_isatty') && posix_isatty($fileDescriptor);
    }

    /**
     * Returns true if STDOUT supports colorization.
     *
     * This code has been copied and adapted from
     * Symfony\Component\Console\Output\StreamOutput.
     */
    public function hasColorSupport(): bool
    {
        if ('Hyper' === getenv('TERM_PROGRAM'))
        {
            return true;
        }

        if ($this->isWindows())
        {
            return (defined('STDOUT') && function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT)) ||
                   false !== getenv('ANSICON') ||
                   'ON' === getenv('ConEmuANSI') ||
                   'xterm' === getenv('TERM');
        }

        if (!defined('STDOUT'))
        {
            return false;
        }

        return $this->isInteractive(STDOUT);
    }

    /**
     * Приглашение к вводу символа от пользователя.
     */
    public function input(string $helloMessage = null): string
    {
        if (null !== $helloMessage)
        {
            echo $helloMessage;
        }

        return (string)fgets(STDIN);
    }

}