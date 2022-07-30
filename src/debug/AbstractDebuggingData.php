<?php declare(strict_types=1);
namespace mrcore\debug;

/**
 * Абстракция отвечает за форматирование ошибки и связанной с ней
 * отладочной информации, а также реализация способа её передачи разработчикам.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractDebuggingData
{
    /**
     * Возвращается текст типа ошибки по её коду.
     */
    protected function _getTypeError(int $errno): string
    {
        return match ($errno) {
            // E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR
            E_ERROR, E_USER_ERROR => 'Fatal error',
            // E_CORE_WARNING, E_COMPILE_WARNING
            E_WARNING, E_USER_WARNING => 'Warning',
            E_NOTICE, E_USER_NOTICE => 'Notice',
            E_STRICT => 'Run-time notices',
            E_RECOVERABLE_ERROR => 'Catchable fatal error',
            E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
            ErrorHandler::E_ASSERT_ERROR => 'Assert error',
            ErrorHandler::E_EXCEPTION_ERROR => 'Exception error',
            default => sprintf('Unknown error type [%u]', $errno),
        };
    }

    /**
     * Возвращается текст ошибки и расширенной информации, если она была указана.
     *
     * @return     array [string, string]
     */
    protected function _parseError(string $errstr): array
    {
        $extendedInfo = '';

        if (false !== ($index = strpos($errstr, '#extended-info#')))
        {
            $extendedInfo = ltrim(substr($errstr, $index + 15));
            $errstr = rtrim(substr($errstr, 0, $index));
        }

        return [$errstr, $extendedInfo];
    }

    /**
     * Приём информации об ошибке и реализация способа передачи её разработчикам.
     *
     * @param      array   $backTrace [[file => string OPTIONAL,
     *                                  line => int OPTIONAL
     *                                  function => string OPTIONAL,
     *                                  class => string OPTIONAL,
     *                                  object => object OPTIONAL,
     *                                  type => string OPTIONAL,
     *                                  args => [string => mixed, ...] OPTIONAL], ...]
     */
    abstract public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void;

}