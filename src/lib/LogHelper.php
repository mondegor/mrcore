<?php declare(strict_types=1);
namespace mrcore\lib;

/**
 * Вспомагательный класс используемый при логировании данных
 * и перепроса их в постоянные источники хранения информации.
 *
 * @author  Andrey J. Nazarov
 */
class LogHelper
{
    /**
     * Виды разделителей.
     */
    public const SEPARATOR = "\t", // разделитель полей используемый в LOAD DATA LOCAL INFILE
                 NEWLINE   = "\n", // разделитель линий используемый в LOAD DATA LOCAL INFILE
                 NULL      = "\N"; // специальный символ используемый в LOAD DATA LOCAL INFILE, который воспринимается как NULL

    /**
     * Название временного файла с обрабатываемыми данными.
     */
    private const TMP_DATA_FILE_NAME = 'data.log.tmp';

    ################################### Properties ###################################

    /**
     * Путь к лог-файлам.
     */
    private string $pathToLog;

    /**
     * Открытый лог файл для записи текущим процессом.
     *
     * @var    resource
     */
    private $logFileHandler = null;

    /**
     * Корректировать обработанные данные в указанной кодировке.
     */
    private string $correctCharset = 'utf-8';

    /**
     * Был или сделан файл с данными.
     */
    private bool $isDataFileMaked = false;

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param string      $pathToLog (/path/to/log/)
     * @param string|null $correctCharset
     */
    public function __construct(string $pathToLog, string $correctCharset = null)
    {
        $path = rtrim($pathToLog, '/');
        $pathTmp = $path . '/tmp';

        if (!is_dir($path) && (file_exists($path) || !mkdir($path)))
        {
            trigger_error(sprintf('Directory "%s" was not created', $path), E_USER_ERROR);
        }

        if (!is_dir($pathTmp) && (file_exists($pathTmp) || !mkdir($pathTmp)))
        {
            trigger_error(sprintf('Directory "%s" was not created', $pathTmp), E_USER_ERROR);
        }

        $this->pathToLog = $path . '/';

        if (null !== $correctCharset)
        {
            $this->correctCharset = $correctCharset;
        }
    }

    /**
     * Экранирование служебных символов перед добавлением в лог файл.
     * \ - без экранирования исчезнет;
     * \0 - экранируется как опасный символ;
     * \n, \t - используются для разметки данных (SEPARATOR, NEWLINE);
     * \r - экранируется, т.к. встречается вместе с \n;
     *
     * @param      string  $string
     * @return     string
     */
    public function escape(string $string): string
    {
        return str_replace(["\\", "\0", "\n", "\r", "\t"], ['\\\\', '\0', '\n', '\r', '\t'], $string);
    }

    /**
     * Разэкранирование служебных символов (функция обратная escape()).
     *
     * @param      string  $string
     * @return     string
     */
    public function unescape(string $string): string
    {
        return str_replace(['\\\\', '\0', '\n', '\r', '\t'], ["\\", "\0", "\n", "\r", "\t"], $string);
    }

    /**
     * Возвращается путь к лог файлу в который будут положены данные.
     *
     * @return     bool
     */
    public function openLogFile(): bool
    {
        if (null === $this->logFileHandler)
        {
            $filePath = $this->pathToLog . getmypid() . '.log';

            if (false === ($fh = fopen($filePath, 'ab')))
            {
                trigger_error(sprintf('Path to log %s is incorrect or permission denied', $filePath), E_USER_WARNING);
                return false;
            }

            $this->logFileHandler = $fh;
        }

        return true;
    }

    /**
     * Запись данных в специально отведённый для процесса лог файл.
     *
     * @param string $data
     * @return     bool
     */
    public function writeToLogFile(string $data): bool
    {
        if ($this->openLogFile())
        {
            return (false !== fwrite($this->logFileHandler, $data . self::NEWLINE));
        }

        return false;
    }

    /**
     * Закрытие специально отведённого для процесса лог файл.
     */
    public function closeLogFile(): void
    {
        if (null !== $this->logFileHandler)
        {
            fclose($this->logFileHandler);
        }
    }

    /**
     * Возвращается путь к файлу данных, который создаётся
     * с помощью метода makeGroupDataFile().
     *
     * @return     string
     */
    public function getGroupDataFilePath(): string
    {
        return $this->isDataFileMaked ? $this->pathToLog . 'tmp/' . self::TMP_DATA_FILE_NAME : '';
    }

    /**
     * Перенос накопленных временных лог файлов во временную директорию,
     * а далее объединение их в один лог файл, путь к которому
     * можно узнать с помощью метода getGroupDataFilePath().
     *
     * @param      int|null     $maxFiles
     * @return     int
     */
    public function makeGroupDataFile(int $maxFiles = null): int
    {
        if (null === $maxFiles)
        {
            $maxFiles = 1024;
        }

        if (!$this->_moveFilesToTmp($maxFiles))
        {
            return 0;
        }

        usleep(250000); // задержка на случай физического отстования HDD

        ##################################################################################

        $pathTmp = $this->pathToLog . 'tmp/';

        if (false === ($dfh = fopen($pathTmp . self::TMP_DATA_FILE_NAME, 'wb')))
        {
            return 0;
        }

        $this->isDataFileMaked = true;
        $count = 0;

        if ($dh = opendir($pathTmp))
        {
            while (false !== ($fileName = readdir($dh)))
            {
                if ('.' === $fileName || '..' === $fileName ||
                        self::TMP_DATA_FILE_NAME === $fileName || is_dir($pathTmp . $fileName))
                {
                    continue;
                }

                if (false === ($fh = fopen($pathTmp . $fileName, 'rb')))
                {
                    continue;
                }

                while (!feof($fh))
                {
                    // :WARNING: если self::NEWLINE будет не \n, то тут нужна другая реализация
                    $buff = rtrim(fgets($fh, 8192), self::NEWLINE);

                    if ('' !== $this->correctCharset)
                    {
                        $buff = iconv($this->correctCharset, $this->correctCharset . '//IGNORE', $buff);
                    }

                    if ('' !== trim($buff))
                    {
                        fwrite($dfh, $buff . self::NEWLINE);
                        $count++;
                    }
                }

                fclose($fh);
                unlink($pathTmp . $fileName);
            }

            closedir($dh);
        }

        fclose($dfh);

        return $count;
    }

    /**
     * Освобождение всех ресурсов используемые объектом.
     */
    public function __destruct()
    {
        $this->closeLogFile();

        if ('' !== ($path = $this->getGroupDataFilePath()))
        {
            unlink($path);
        }
    }

    ##################################################################################

    /**
     * Перенос накопленных временных лог файлов во временную директорию.
     *
     * @param      int $maxFiles
     * @return     bool
     */
    protected function _moveFilesToTmp(int $maxFiles): bool
    {
        $path = $this->pathToLog;
        $pathTmp = $this->pathToLog . 'tmp/';

        if (false === ($dh = opendir($path)))
        {
            return false;
        }

        while ($maxFiles > 0 && (false !== ($fileName = readdir($dh))))
        {
            if ('.' === $fileName || '..' === $fileName || is_dir($path . $fileName))
            {
                continue;
            }

            // файл будет переименован только если в него ничего не пишет другой процесс,
            // это обстоятельство гарантирует целостность файла
            if (@rename($path . $fileName, $pathTmp . $fileName))
            {
                $maxFiles--;
            }
        }

        closedir($dh);

        return true;
    }

}