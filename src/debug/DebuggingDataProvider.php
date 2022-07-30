<?php declare(strict_types=1);
namespace mrcore\debug;

/**
 * Класс являеся контейнером разных способов передачи разработчикам
 * ошибок и связанной с ними отладочной информации.
 *
 * @author  Andrey J. Nazarov
 */
class DebuggingDataProvider extends AbstractDebuggingData
{
    /**
     * Название директории, куда будут сохранятся хэши ошибок.
     */
    private const DIR_ERROR_HASHES = 'errhashes';

    /**
     * Типы источников разных способов уведомления об ошибках.
     */
    public const TYPE_DISPLAY = 0, // в браузер, в консоль в STDOUT или STDERR
                 TYPE_LOG = 1, // в лог файл
                 TYPE_MAIL = 2; // на емаил

    ################################### Properties ###################################

    /**
     * Путь, куда будут сохранятся хеши ошибок,
     * чтобы ограничить генерацию одинаковых ошибок.
     */
    private string $pathToErrorHashes = '';

    /**
     * Список хешей уже случившихся ошибок.
     *
     * @var  string[]
     */
    private array $errorHashes = [];

    #################################### Methods #####################################

    /**
     * Список источников разных способов уведомления об ошибках:
     *     display -> TextDebuggingData, HtmlDebuggingData;
     *     log -> FileDebuggingData;
     *     mail -> MailDebuggingData;
     *
     * @param  array  $debuggingDataList [DebuggingDataProvider::TYPE_DISPLAY => AbstractDebuggingData,
     *                                    DebuggingDataProvider::TYPE_LOG => AbstractDebuggingData,
     *                                    DebuggingDataProvider::TYPE_MAIL => AbstractDebuggingData]
     */
    public function __construct(private array $debuggingDataList, string $pathToLogs = null)
    {
        if (null !== $pathToLogs)
        {
            $this->pathToErrorHashes = rtrim($pathToLogs, '/') . '/' . self::DIR_ERROR_HASHES;
        }
    }

    /**
     * Приём информации об ошибке и передача её зарегистрированными способами.
     *
     * @inheritdoc
     */
    public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void
    {
        if ($this->_isErrorRigistered($errno, $errstr, $errfile, $errline))
        {
            return;
        }

        if (isset($this->debuggingDataList[self::TYPE_DISPLAY]))
        {
            $this->debuggingDataList[self::TYPE_DISPLAY]->perform($errno, $errstr, $errfile, $errline, $backTrace);
        }
        // если вывод ошибок на экран не определён
        else
        {
            // то обязательно должен быть определена запись ошибок в лог
            if (!isset($this->debuggingDataList[self::TYPE_LOG]))
            {
                trigger_error('The class for displaying errors and the class for recording errors to log are not registered', E_USER_ERROR);
            }
        }

        ##################################################################################

        if ('' !== $this->pathToErrorHashes)
        {
            if (!is_dir($this->pathToErrorHashes) && (file_exists($this->pathToErrorHashes) || !mkdir($this->pathToErrorHashes)))
            {
                trigger_error(sprintf('Directory "%s" was not created', $this->pathToErrorHashes), E_USER_ERROR);
            }

            // одинаковые ошибки пишутся в лог файл и отправляются на email-ы не более 1 раза в час
            $errorHashFile = sprintf('%s/%s-%s', $this->pathToErrorHashes, array_key_last($this->errorHashes), date('d-H'));

            if (file_exists($errorHashFile))
            {
                return;
            }

            // запоминается, что ошибка произошла
            touch($errorHashFile);
        }

        if (isset($this->debuggingDataList[self::TYPE_LOG]))
        {
            // сообщения об ошибках пишутся всегда за исключением одинаковых
            $this->debuggingDataList[self::TYPE_LOG]->perform($errno, $errstr, $errfile, $errline, $backTrace);
        }

        ##################################################################################

        if (isset($this->debuggingDataList[self::TYPE_MAIL]))
        {
            $this->debuggingDataList[self::TYPE_LOG]->perform($errno, $errstr, $errfile, $errline, $backTrace);
        }
    }

    ##################################################################################

    /**
     * Возвращается регистрировалась ли указанная ошибка.
     * И если нет, то она также регистрируется.
     */
    protected function _isErrorRigistered(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $errorHash = md5(sprintf('%u|%s|%s|%u', $errno, $errstr, $errfile, $errline));

        if (isset($this->errorHashes[$errorHash]))
        {
            return true;
        }

        $this->errorHashes[$errorHash] = true;

        return false;
    }

}