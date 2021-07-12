<?php declare(strict_types=1);
namespace mrcore\debug;
use Error;
use ErrorException;
use Throwable;

/**
 * Класс иницилизирует встроенный php обработчик ошибок,
 * как встроенных так и испускаемых при помощи trigger_error,
 * также устанавливает свои методы errorHandler, assertHandler и exceptionHandler
 * в качестве обработчиков ошибок и исключений.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore.debug
 */
class ErrorHandler
{
    /**
     * Вводится код ошибки для отображения assert-ов.
     */
    public const E_USER_ASSERT = 65536;

    /**
     * Режимы отображения ошибок.
     *
     * @const  int
     */
    public const DISPLAY_AND_STOP = 0, // - любая ошибка будет считаться фатальной и при её отображении работа приложения будет остановлена;
                 DISPLAY_AND_CONTINUE = 1, // ошибки будут отображаться по мере их возникновения (при работе шаблонизатора некоторые ошибки могут затеряться в тегах с отключённой видимостью);
                 DISPLAY_ALL_AFTER_APP_FINISHED = 2; // все ошибки (за исключением фатальных) будут отображены во время завершения работы приложения;

    ################################### Properties ###################################

    ///**
    // * Флаг отладочного режима.
    // *
    // * @var    bool
    // */
    //private bool $_isDebugMode;

    /**
     * Флаг режима работы assert сообщений.
     *
     * @var    bool
     */
    private bool $_isAssertMode;

    /**
     * Текущий режим отображения ошибок:
     * (DISPLAY_AND_STOP, DISPLAY_AND_CONTINUE, DISPLAY_ALL_AFTER_APP_FINISHED)
     *
     * @var    int
     */
    private int $_errorDisplayMode;

    /**
     * Поставщик различных вариантов отображения и отправки ошибок разработчикам.
     *
     * @var    AbstractDebuggingData
     */
    private AbstractDebuggingData $_debuggingDataProvider;

    /**
     * Массив отложенных ошибок, которые будут
     * выведены в самом конце работы приложения.
     *
     * @var    array
     */
    private array $_delayedErrors = [];

    /**
     * Стэк вызовов функций и методов.
     *
     * @var    array|null [[file => string OPTIONAL,
     *                      line => int OPTIONAL
     *                      function => string OPTIONAL,
     *                      class => string OPTIONAL,
     *                      object => object OPTIONAL,
     *                      type => string OPTIONAL,
     *                      args => [string => mixed, ...] OPTIONAL], ...]
     */
    private $_trace = null;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param bool  $isDebugMode
     * @param bool  $isAssertMode
     * @param int   $errorDisplayMode (DISPLAY_AND_STOP, DISPLAY_AND_CONTINUE, DISPLAY_ALL_AFTER_APP_FINISHED)
     * @param AbstractDebuggingData $debuggingDataProvider
     */
    public function __construct(bool $isDebugMode, bool $isAssertMode,
                                int $errorDisplayMode, AbstractDebuggingData $debuggingDataProvider)
    {
        // $this->_isDebugMode = $isDebugMode;
        $this->_isAssertMode = $isAssertMode;
        $this->_errorDisplayMode = $errorDisplayMode;
        $this->_debuggingDataProvider = &$debuggingDataProvider;

        if ($isDebugMode)
        {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }

        if ($isAssertMode)
        {
            assert_options(ASSERT_ACTIVE,  1);
            assert_options(ASSERT_WARNING, 0);
            assert_options(ASSERT_BAIL, self::DISPLAY_AND_STOP === $errorDisplayMode ? 1 : 0);
            assert_options(ASSERT_CALLBACK, [&$this, 'assertHandler']);
        }
        else
        {
            assert_options(ASSERT_ACTIVE, 0);
        }

        ##################################################################################

        set_error_handler([&$this, 'errorHandler']);
        set_exception_handler([&$this, 'exceptionHandler']);
    }

    /**
     * Обработчик php ошибок.
     *
     * @param      int     $errno
     * @param      string  $errstr
     * @param      string  $errfile
     * @param      int     $errline
     */
    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        // :WARNING: на момент вызова "заглушенной" ошибки символом @
        //           системная переменная ini_get('error_reporting')
        //           средствами PHP временно сбрасывается в 0
        $error_reporting = (int)ini_get('error_reporting');

        // если все ошибки пропускаются или только заданные
        if (E_ALL === $error_reporting || ($error_reporting & $errno))
        {
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
            $isFatalError = ((E_USER_ERROR | E_ERROR) & $errno) ||
                            (0 === strncmp($errstr, 'require', 7)) ||
                            (self::DISPLAY_AND_STOP === $this->_errorDisplayMode);

            $this->_execute($errno, $errstr, $errfile, $errline, $isFatalError);
        }
    }

    /**
     * Обработчик assert-ов (утверждений).
     *
     * @param      string  $errfile
     * @param      int     $errline
     * @param      string  $errcode
     * @param      string  $description
     */
    public function assertHandler(string $errfile, int $errline, string $errcode, string $description): void
    {
        $this->_prepareTrace($errfile, $errline);

        // срезается дополнительная информация, в случае когда
        // испльзуется исключение: assert(1 === 2, new AssertionError('1 is not 2!'))
        if (false !== ($index = mb_strrpos($description, 'Stack trace:')))
        {
            $description = mb_substr($description, 0, $index);

            if (false !== ($index = mb_strrpos($description, ' in ')))
            {
                $description = mb_substr($description, 0, $index);
            }
        }

        $this->_execute(self::E_USER_ASSERT, $description, $errfile, $errline);
    }

    /**
     * Обработчик неперехваченных php исключений.
     *
     * @param      Throwable  $exc
     */
    public function exceptionHandler(Throwable $exc): void
    {
        $this->_trace = $exc->getTrace();

        $errno = 0;

        if ($exc instanceof Error)
        {
            $errno = $exc->getCode();
        }
        else if ($exc instanceof ErrorException)
        {
            $errno = $exc->getSeverity();
        }

        if (0 === $errno)
        {
            $errno = E_RECOVERABLE_ERROR;
        }

        $this->errorHandler($errno, $exc->getMessage(), $exc->getFile(), $exc->getLine());
    }

    /**
     * Деструктор класса.
     */
    public function __destruct()
    {// var_dump('call __destruct() of class: ' . get_class($this));

        // :WARNING: режим отладки устанавливается явно в DISPLAY_AND_CONTINUE,
        //           иначе если возникнут более поздние ошибки, то они могут не отобразиться
        if (self::DISPLAY_ALL_AFTER_APP_FINISHED === $this->_errorDisplayMode)
        {
            $this->_errorDisplayMode = self::DISPLAY_AND_CONTINUE;
        }

        $this->_showDelayedErrors();

        ##################################################################################

        restore_error_handler();
        restore_exception_handler();

        if ($this->_isAssertMode)
        {
            assert_options(ASSERT_CALLBACK, null);
        }
    }

    /**
     * Предварительная обработка отладочной информации.
     *
     * @param      string  $errfile
     * @param      int     $errline
     */
    private function _prepareTrace(string $errfile, int $errline): void
    {
        if (null === $this->_trace)
        {
            $this->_trace = debug_backtrace();

            // выкидывается информация о вхождении в данный метод класса (_prepareTrace)
            // и вызывашего его errorHandler или assertHandler
            $first = array_shift($this->_trace);
            array_shift($this->_trace);
        }
        // если $this->_trace был получен в exceptionHandler
        else if (!empty($this->_trace))
        {
            $first = $this->_trace[key($this->_trace)];
        }

        $count = count($this->_trace);

        // удаляется запись из стека, которая уже и так выводится в сообщении
        if ($count > 0)
        {
            $second = array_shift($this->_trace);

            if (!isset($second['file']) || $errfile !== $second['file'] || $errline !== $second['line'])
            {
                array_unshift($this->_trace, $second);
            }
            else
            {
                $count--;
            }
        }

        ##################################################################################

        if ($count > 0 && isset($this->_trace[$count - 1]['file']))
        {
            $first['file'] = $this->_trace[$count - 1]['file'];
        }

        // вставляется информация о точке входа приложения
        $this->_trace[] = array
        (
            'function' => '{main}',
            'file'     => $first['file'] ?? '',
            'line'     => 1,
        );
    }

    /**
     * Передача ошибки текущему провайдеру, для того чтобы он её
     * отформатировал и зафиксировал всеми известными ему способами.
     *
     * @param      int     $errno
     * @param      string  $errstr
     * @param      string  $errfile
     * @param      int     $errline
     * @param      bool    $isFatalError
     */
    private function _execute(int $errno, string $errstr, string $errfile, int $errline, bool $isFatalError = false): void
    {
        if ($isFatalError || self::DISPLAY_ALL_AFTER_APP_FINISHED !== $this->_errorDisplayMode)
        {
            $this->_showDelayedErrors();
            $this->_debuggingDataProvider->perform($errno, $errstr, $errfile, $errline, $this->_trace);

            if ($isFatalError)
            {
                exit(1);
            }
        }
        // в режиме отложенных ошибок все echo ошибки накапливаются в массиве,
        // а затем разом отправляются клиенту (за исключением фатальных)
        else
        {
            ob_start();
            $this->_debuggingDataProvider->perform($errno, $errstr, $errfile, $errline, $this->_trace);
            $this->_delayedErrors[] = ob_get_clean();
        }

        $this->_trace = null;
    }

    /**
     * Отображение накопленных отложенных ошибок.
     */
    private function _showDelayedErrors(): void
    {
        if (!empty($this->_delayedErrors))
        {
            foreach ($this->_delayedErrors as $_error)
            {
                echo $_error;
            }

            $this->_delayedErrors = [];
        }
    }

}