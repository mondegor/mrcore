<?php declare(strict_types=1);
namespace mrcore\debug;
use RuntimeException;

require_once 'mrcore/debug/AbstractDebuggingData.php';

/**
 * Класс являеся контейнером разных способов передачи разработчикам
 * ошибок и связанной с ними отладочной информации.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/debug
 */
class DebuggingDataProvider extends AbstractDebuggingData
{
    /**
     * Флаг отладочного режима (true - включен, false - отключен).
     *
     * @var    bool
     */
    private bool $_isDebugMode;

    /**
     * Список источников разных способов уведомления об ошибках.
     *     console -> TextDebuggingData, HtmlDebuggingData;
     *     log -> FileDebuggingData;
     *     mail -> MailDebuggingData;
     *
     * @var    array [console => [source => string, init => Closure],
     *                log     => [source => string, init => Closure],
     *                email   => [source => string, init => Closure]]
     */
    private array $_debuggingDataList;

    /**
     * Название директории, куда будут сохранятся хэши ошибок,
     * чтобы ограничить генерацию одинаковых ошибок.
     *
     * @var    string
     */
    private string $_pathToErrorHashes = '';

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      bool $isDebugMode
     * @param      array $debuggingDataList [console => [source => string, init => Closure],
     *                                       log     => [source => string, init => Closure],
     *                                       email   => [source => string, init => Closure]]
     * @param      string $pathToErrorHashes OPTIONAL
     */
    public function __construct(bool $isDebugMode, array $debuggingDataList, string $pathToErrorHashes = null)
    {
        $this->_isDebugMode = $isDebugMode;
        $this->_debuggingDataList = $debuggingDataList;

        if (null !== $pathToErrorHashes)
        {
            $this->_pathToErrorHashes = $pathToErrorHashes;
        }
    }

    /**
     * Приём информации об ошибке и передача её зарегистрированным способам.
     *
     * {@inheritdoc}
     */
    /*__override__*/ public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void
    {
        $errorHash = md5(sprintf('%u|%s|%s|%u', $errno, $errstr, $errfile, $errline));

        static $errorHashes = array();

        // если такая ошибка уже происходила при работе приложения, то она игнорируется
        if (isset($errorHashes[$errorHash]))
        {
            return;
        }

        $errorHashes[$errorHash] = true;

        ##################################################################################

        // только в режиме отладки выводятся сообщения об ошибках в консоль
        if ($this->_isDebugMode)
        {
            try
            {
                $debug = &$this->_getDebuggingData('console');
                $debug->perform($errno, $errstr, $errfile, $errline, $backTrace);
            }
            catch (RuntimeException $e)
            {
                trigger_error(sprintf('The class for displaying errors is not registered'), E_USER_WARNING);
            }
        }

        ##################################################################################

        if (!empty($this->_pathToErrorHashes) && file_exists($this->_pathToErrorHashes))
        {
            // одинаковые ошибки пишутся в лог файл и отправляются на email-ы не более 1 раза в час
            $errorHashFile = sprintf('%s/%s-%s', $this->_pathToErrorHashes, $errorHash, date('d-H'));

            if (file_exists($errorHashFile))
            {
                return;
            }

            // запоминается, что ошибка произошла
            touch($errorHashFile);
        }

        try
        {
            // сообщения об ошибках пишутся всегда за исключением одинаковых
            $debug = &$this->_getDebuggingData('log');
            $debug->perform($errno, $errstr, $errfile, $errline, $backTrace);
        }
        catch (RuntimeException $e)
        {
            trigger_error(sprintf('The class for recording errors is not registered'), E_USER_WARNING);
        }

        ##################################################################################

        // только в выключенном режиме отладки сообщения отправляются на email-ы
        if (!$this->_isDebugMode)
        {
            try
            {
                $debug = &$this->_getDebuggingData('email');
                $debug->perform($errno, $errstr, $errfile, $errline, $backTrace);
            }
            catch (RuntimeException $e)
            {
                // некритичный способ доставки сообщения об ошибках, поэтому error_log не требуется
            }
        }
    }

    /**
     * Возвращение объекта указанного способа уведомления об ошибках.
     *
     * @param      string  $type (console, log, email, ...)
     * @return     AbstractDebuggingData
     * @throws     RuntimeException
     */
    public function &_getDebuggingData(string $type): AbstractDebuggingData
    {
        if (!isset($this->_debuggingDataList[$type]))
        {
            throw new RuntimeException(sprintf('DebuggingData type %s is not found', $type));
        }

        if (!isset($this->_debuggingDataList[$type]['cache']))
        {
            $source = $this->_debuggingDataList[$type]['source'];
            require_once strtr(ltrim($source, '\\'), '\\', '/') . '.php';

            // если при инициализации объект не был создан, значит он убирается из конфигурации
            if (null === ($obj = $this->_debuggingDataList[$type]['init']($source)))
            {
                unset($this->_debuggingDataList[$type]);
                throw new RuntimeException(sprintf('DebuggingData type %s is disabled', $type));
            }

            if (!($obj instanceof AbstractDebuggingData))
            {
                trigger_error(sprintf('The created object of class %s is not an inheritor of class %s', $obj, AbstractDebuggingData::class), E_USER_ERROR);
            }

            $this->_debuggingDataList[$type]['cache'] = &$obj;
        }

        return $this->_debuggingDataList[$type]['cache'];
    }

}