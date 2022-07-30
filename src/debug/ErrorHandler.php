<?php declare(strict_types=1);
namespace mrcore\debug;
use Closure;
use Throwable;

/**
 * Класс инициализирует встроенный php обработчик ошибок,
 * как встроенных так и испускаемых при помощи trigger_error,
 * также устанавливает свои методы errorHandler, assertHandler и exceptionHandler
 * в качестве обработчиков ошибок и исключений.
 *
 * @author  Andrey J. Nazarov
 */
class ErrorHandler
{
    /**
     * Вводится код ошибки для отображения ассертов.
     */
    public const E_ASSERT_ERROR = 2097152;

    /**
     * Вводится код ошибки для отображения эксепшенов.
     */
    public const E_EXCEPTION_ERROR = 4194304;

    /**
     * Стек вызовов функций и методов.
     *
     * @var    array|null [[file => string OPTIONAL,
     *                      line => int OPTIONAL
     *                      function => string OPTIONAL,
     *                      class => string OPTIONAL,
     *                      object => object OPTIONAL,
     *                      type => string OPTIONAL,
     *                      args => [string => mixed, ...] OPTIONAL], ...]
     */
    private ?array $trace = null;

    /**
     * Формируется на основе вызова $debuggingDataCreater.
     */
    private ?AbstractDebuggingData $debuggingData = null;

    #################################### Methods #####################################

    /**
     * @param  Closure $debuggingDataCreater // return AbstractDebuggingData
     */
    public function __construct(bool $isDebugMode,
                                private bool $isAssertMode,
                                bool $isDisplayErrors,
                                private bool $anyErrorIsFatal,
                                private Closure $debuggingDataCreater)
    {
        if ($isDebugMode)
        {
            error_reporting(E_ALL);
        }

        if ($isAssertMode)
        {
            assert_options(ASSERT_ACTIVE,  1);
            assert_options(ASSERT_EXCEPTION, 0);
            assert_options(ASSERT_WARNING, 0);
            // assert_options(ASSERT_BAIL, 1);
            assert_options(ASSERT_CALLBACK, [$this, 'assertHandler']);
        }
        else
        {
            assert_options(ASSERT_ACTIVE, 0);
        }

        ini_set('display_errors', $isDisplayErrors ? '1' : '0');
        ini_set('display_startup_errors', $isDisplayErrors ? '1' : '0');

        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * Обработчик php ошибок.
     */
    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        // :WARNING: на момент вызова "заглушенной" ошибки символом @
        //           системная функция error_reporting()
        //           средствами PHP временно возвращает значение 0,
        //           но если было ранее установлено E_ALL, то возвращается значение:
        //           4437 = (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_COMPILE_WARNING | E_STRICT)

        $isFatalError = ((E_ERROR | E_USER_ERROR | self::E_ASSERT_ERROR | self::E_EXCEPTION_ERROR) & $errno) > 0;
        $errorReporting = error_reporting();

        // если это фатальная ошибка или если ошибка не "заглушена" символом @
        if ($isFatalError || E_ALL === $errorReporting || ($errorReporting & $errno) > 0)
        {
            // :TODO: пересмотреть данные проверки

            // :WARNING: данная ошибка игнорируется, т.к. одновременно с ней
            // всегда генерируется ещё одна похожая ошибка, поэтому отображается только она
            if (0 === strncmp($errstr, 'include_once():', 15))
            {
                return;
            }

            $this->_prepareTrace($errfile, $errline);

            // Fatal error генерируется в случае:
            // - если это собственно Fatal error;
            // - если подключаемый файл не найден (для конструкций require и require_once)
            // - включен специальный режим разработчика
            if (!$isFatalError)
            {
                $isFatalError = (0 === strncmp($errstr, 'require', 7)) || $this->anyErrorIsFatal;
            }

            $this->_execute($errno, $errstr, $errfile, $errline, $isFatalError);
        }
    }

    /**
     * Обработчик assert-ов (утверждений).
     */
    public function assertHandler(string $errfile, int $errline, string|null $assertion, string $description = ''): void
    {
        $this->_prepareTrace($errfile, $errline);
        $this->_execute(self::E_ASSERT_ERROR, $description, $errfile, $errline, true);
    }

    /**
     * Обработчик не перехваченных php исключений.
     */
    public function exceptionHandler(Throwable $e): void
    {
        $this->trace = $e->getTrace();
        $this->errorHandler(self::E_EXCEPTION_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());
    }

    public function __destruct()
    {
        restore_error_handler();
        restore_exception_handler();

        if ($this->isAssertMode)
        {
            assert_options(ASSERT_CALLBACK);
        }
    }

    /**
     * Предварительная обработка отладочной информации.
     */
    protected function _prepareTrace(string $errfile, int $errline): void
    {
        if (null === $this->trace)
        {
            $this->trace = debug_backtrace();

            // выкидывается информация о вхождении в данный метод класса (_prepareTrace)
            // и вызывавшего его errorHandler или assertHandler
            $first = array_shift($this->trace);
            array_shift($this->trace);
        }
        // если $this->_trace был получен в exceptionHandler
        else if (!empty($this->trace))
        {
            $first = $this->trace[key($this->trace)];
        }

        $count = count($this->trace);

        // удаляется запись из стека, которая уже и так выводится в сообщении
        if ($count > 0)
        {
            $second = array_shift($this->trace);

            if (!isset($second['file']) || $errfile !== $second['file'] || $errline !== $second['line'])
            {
                array_unshift($this->trace, $second);
            }
            else
            {
                $count--;
            }
        }

        ##################################################################################

        if ($count > 0 && isset($this->trace[$count - 1]['file']))
        {
            $first['file'] = $this->trace[$count - 1]['file'];
        }

        // вставляется информация о точке входа приложения
        $this->trace[] = array
        (
            'function' => '{main}',
            'file'     => $first['file'] ?? '',
            'line'     => 1,
        );
    }

    /**
     * Передача ошибки текущему провайдеру, для того чтобы он её
     * отформатировал и зафиксировал всеми известными ему способами.
     */
    protected function _execute(int $errno, string $errstr, string $errfile, int $errline, bool $isFatalError = false): void
    {
        if (null === $this->debuggingData)
        {
            $debuggingDataCreater = $this->debuggingDataCreater;
            $this->debuggingData = $debuggingDataCreater();
        }

        $this->debuggingData->perform($errno, $errstr, $errfile, $errline, $this->trace);

        $this->trace = null;

        if ($isFatalError)
        {
            exit(1);
        }
    }

}