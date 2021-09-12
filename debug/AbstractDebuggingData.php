<?php declare(strict_types=1);
namespace mrcore\debug;
use mrcore\services\TraitServiceInjection;

require_once 'mrcore/debug/ErrorHandler.php';
require_once 'mrcore/services/TraitServiceInjection.php';

/**
 * Класс отвечает за форматирование ошибки и связанной с ней
 * отладочной информации, а также реализация способа её передачи разработчикам.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/debug
 */
abstract class AbstractDebuggingData
{
    use TraitServiceInjection;

    /**
     * Список слов, которые нужно скрывать при записи в логи.
     *
     * @const    array [string, ...]
     */
    protected const WORDS_TO_HIDE = ['pass', 'pw'];

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _getSubscribedServices(): array
    {
        return array
        (
            'global.env' => true,
        );
    }

    /**
     * Возвращение текста типа ошибки по её коду.
     *
     * @param      int  $errno
     * @return     string
     */
    public static function getTypeError(int $errno): string
    {
        switch ($errno)
        {
            case E_ERROR:
            // case E_PARSE:
            // case E_CORE_ERROR:
            // case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $typeError = 'Fatal error';
                break;

            case E_WARNING:
            // case E_CORE_WARNING:
            // case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $typeError = 'Warning';
                break;

            case E_NOTICE:
            case E_USER_NOTICE:
                $typeError = 'Notice';
                break;

            case ErrorHandler::E_USER_ASSERT:
                $typeError = 'Warning [assert]';
                break;

            case E_STRICT:
                $typeError = 'Run-time notices';
                break;

            case E_RECOVERABLE_ERROR:
                $typeError = 'Catchable fatal error';
                break;

            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $typeError = 'Deprecated';
                break;

            default:
                $typeError = sprintf('Unkown error type [%u]', $errno);
                break;
        }

        return 'MR#' . $typeError;
    }

    ##################################################################################

    /**
     * Приём информации об ошибке и реализация способа передачи её разработчикам.
     *
     * @param      int     $errno
     * @param      string  $errstr
     * @param      string  $errfile
     * @param      int     $errline
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