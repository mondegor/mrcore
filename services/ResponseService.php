<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\view\InterfaceTemplater;
use mrcore\view\TemplaterNative;
use mrcore\base\BuilderLink;

require_once 'mrcore/services/InterfaceInjectableService.php';
require_once 'mrcore/view/InterfaceTemplater.php';

// /*MrApp*/ require_once 'mrcore/MrCache.php';
// require_once 'mrcore/MrEvent.php';
// /*MrApp*/ require_once 'mrcore/MrObject.php';
// /*MrApp*/ require_once 'mrcore/MrUser.php';

// used const: MRCORE_PROJECT_DIR_TMP
// used var: $_ENV['MRCORE_DEBUG']

/**
 * Класс-ответчик на внешний запрос.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/services
 */
class ResponseService implements InterfaceInjectableService
{
    /**
     * Часто используемые типы ответа сервера
     *
     * @const   string
     */
    public const CONTENT_TYPE_HTML = 'text/html',
                 CONTENT_TYPE_JSON = 'application/json',
                 CONTENT_TYPE_XML  = 'application/xml',
                 CONTENT_TYPE_CSV  = 'text/csv';

    /**
     * Формат ответа сервера.
     *
     * Популярные типы ответа сервера:
     *   - text/html
     *   - application/json
     *   - application/xml
     *   - text/csv
     *
     * @var    string
     */
    public string $contentType = 'text/html';

    /**
     * Кодировка ответа сервера.
     *
     * @var    string
     */
    public string $charset = 'utf-8';

    /**
     * Флаг кэширование ответа сервера.
     * Самой системой здесь кэширования не происходит, если кэширование
     * отключено, то посылается информация браузеру, о том, что
     * данную страницу кэшировать не следует.
     *
     * @var    bool
     */
    public bool $isCache = true;

    /**
     * Время последнего изменения ответа сервера (секунды, GMT, timestamp).
     *
     * @var    int
     */
    public int $lastModified = 0;

    /**
     * Название класса шаблонизатора
     * используемого при формировании ответа.
     *
     * @var    string
     */
    public string $templaterClassName = TemplaterNative::class;

    /**
     * Данные для ответа сервером клиенту:
     *   NULL - данные отправлять не планируется или они не сформированны;
     *   STRING - данные возвращаются в виде строки;
     *   ARRAY - данные передаются в шаблонизатор, после этого возвращаются в виде строки;
     *
     * @var    string|array|null
     */
    public $outputData = null;

    /**
     * Заголовки http запроса.
     *
     * @var    array
     */
    private array $_headers = array();

    /**
     * Код ответа сервера HTTP/1.1
     *
     * 100-199 Информационный
     * 200-299 Запрос клиента успешен
     * 300-399 Запрос клиента переадресован, необходимы дальнейшие действия
     * 400-499 Запрос клиента является неполным
     * 500-599 Ошибки сервера
     *
     * +200 OK
     * +201 Created (Создано);
     * +202 Accepted (Принято);
     *  203 Non-Authoritative Information (Информация не авторитетна);
     * +204 No Content (Нет содержимого);
     *  205 Reset Content (Сбросить содержимое);
     *  206 Partial Content (Частичное содержимое);
     *  207 Multi-Status (Многостатусный);
     *  208 Already Reported (Уже сообщалось);
     *  226 IM Used (Использовано IM).
     *  300 Multiple Choices (Множество выборов)
     * +301 Moved Permanently (Перемещено окончательно)
     * +302 Found (Найдено)
     *  303 See Other (Смотреть другое)
     *  304 Not Modified (Не изменялось)
     *  305 Use Proxy (Использовать прокси)
     *  307 Temporary Redirect (Временное перенаправление)
     *  400 Bad Request (Плохой запрос)
     * +401 Unauthorized (Неавторизован)
     *  402 Payment Required (Необходима оплата)
     * +403 Forbidden (Запрещено)
     * +404 Not Found (Не найдено)
     *  405 Method Not Allowed (Метод не поддерживается)
     *  406 Not Acceptable (Не приемлемо)
     *  407 Proxy Authentication Required (Необходима аутентификация прокси)
     *  408 Request Timeout (Время ожидания истекло)
     *  409 Conflict (Конфликт)
     *  410 Gone (Удалён)
     *  411 Length Required (Необходима длина)
     *  412 Precondition Failed (Условие «ложно»)
     *  413 Request Entity Too Large (Размер запроса слишком велик)
     *  414 Request-URI Too Long (Запрашиваемый URI слишком длинный)
     *  415 Unsupported Media Type (Неподдерживаемый тип данных)
     *  416 Requested Range Not Satisfiable (Запрашиваемый диапазон не достижим)
     *  417 Expectation Failed (Ожидаемое не приемлемо)
     *  500 Internal Server Error (Внутренняя ошибка сервера)
     *  501 Not Implemented (Не реализовано)
     * +502 Bad Gateway (Плохой шлюз)
     * +503 Service Unavailable (Сервис недоступен)
     *  504 Gateway Timeout (Шлюз не отвечает)
     *  505 HTTP Version Not Supported (Версия HTTP не поддерживается)
     *
     * @var    int
     */
    private int $_codeAnswer = 200;

    /**
     * Ссылка на переадресуемый документ.
     *
     * @var    BuilderLink
     */
    private ?BuilderLink $_redirectLink = null;

    /**
     * Ссылка на объект используемого шаблонизатора.
     * Объект создаётся один раз методом $this->getTemplate() используя
     * название класса хранящегося в переменной $this->templaterClassName,
     * далее поменять шаблонизатор уже нельзя.
     *
     * @var    InterfaceTemplater
     */
    private ?InterfaceTemplater $_templater = null;

    #################################### Methods #####################################

    /**
     * Добавление заголовка http запроса.
     * :TODO: можно реализовать на основе ArrayAccess
     *
     * @param      string  $header
     * @param      boolean  $replace OPTIONAL
     * @return     self
     */
    public function &addHeader(string $header, bool $replace = false): self
    {
        $this->_headers[] = array($header, $replace);

        return $this;
    }

    /**
     * Возвращение текущего ответа сервера.
     *
     * @return     int
     */
    public function getAnswer(): int
    {
        return $this->_codeAnswer;
    }

    /**
     * Установка ответа сервера.
     *
     * @param      int  $code
     */
    public function setAnswer(int $code): void
    {
        if (null === $this->_redirectLink)
        {
            // если код ошибки "доступ запрещён", но пользователь
            // не авторизован, то код заменяется на 401
            if (403 === $code && MrUser::$info['id'] <= MrUser::ID_GUEST)
            {
                $code = 401;
            }
        }
        else
        {
            assert($code >= 300 && $code < 400);

            if ($code < 300 || $code >= 400)
            {
                $code = 302;
            }
        }

        $this->_codeAnswer = $code;
    }

    /**
     * Установка ссылки-объекта на переадресуемый документ.
     *
     * @param      BuilderLink  $link
     * @param      int  $code (должен быть указан код из множества кодов 3xx: Redirection)
     */
    public function setRedirect(BuilderLink $link, int $code): void
    {
        assert($code >= 300 && $code < 400);

        $this->_redirectLink = &$link;
        $this->_codeAnswer = ($code >= 300 && $code < 400 ? $code : 302);
    }

//     /**
//      * Отмена редиректа, который был задан ранее.
//      */
//     public function cancelRedirect()
//     {
//         $link = null;
//         $this->_redirectLink = &$link;
//         $this->_codeAnswer = 200;
//     }

    /**
     * Был ли установлен редирект на переадресуемый документ?
     *
     * @return     bool
     */
    public function isRedirect(): bool
    {
        return (null !== $this->_redirectLink);
    }

    /**
     * Возвращение ссылки на объект используемого
     * шаблонизатора при формировании ответа сервера.
     *
     * @return     InterfaceTemplater
     */
    public function &getTemplater(): InterfaceTemplater
    {
        if (null === $this->_templater)
        {
            $this->_templater = &MrObject::factory($this->templaterClassName);
        }

        return $this->_templater;
    }

    /**
     * Отсылка ответа инициатору запроса.
     */
    public function commit(): void
    {
        // устанавливается тип ответа сервера и его кодировка
        header('Content-Type: ' . $this->contentType . '; charset=' . $this->charset);

        switch ($this->_codeAnswer)
        {
            case 200:
                break;

            case 301:
                // отправка http заголовка, страница была окончательно перемещена
                // Код 301. Страница окончательно перемещена
                header('HTTP/1.0 301 Moved Permanently');
                break;

            case 302:
                // отправка http заголовка, страница была перенесена
                // Код 302. Страница временно перемещена
                header('HTTP/1.0 302 Found');
                break;

            case 404:
                // отправка http заголовка, об отсутствии документа
                // Ошибка 404. Запрошенная пользователем страница не найдена
                header('HTTP/1.0 404 Not Found');
                break;

            case 401:
                // отправка http заголовка, если доступ к документу запрещён
                // Ошибка 401. Для доступа к запрошенной пользователем странице требуется авторизация
                header('HTTP/1.0 401 Unauthorized');
                break;

            case 403:
                // отправка http заголовка, если доступ к документу запрещён
                // Ошибка 403. Доступ был запрещён к запрошенной пользователем странице
                header('HTTP/1.0 403 Forbidden');
                break;

            case 400:
                // отправка http заголовка, о некорректном запросе
                // Ошибка 400. Запрос не корректный
                header('HTTP/1.0 400 Bad Request');
                break;

            case 201:
                // Код 201. Создано
                header('HTTP/1.0 201 Created');
                break;

            case 202:
                // Код 202. Принято
                header('HTTP/1.0 202 Accepted');
                break;

            case 204:
                // Код 204. Нет содержимого
                header('HTTP/1.0 204 No Content');
                break;

            default:
                trigger_error(sprintf('Неизвестный код ответа сервера %s', $this->_codeAnswer), E_USER_WARNING);
                break;
        }

        // отправка дополнительных http заголовков документа
        foreach ($this->_headers as $header)
        {
            header($header[0], $header[1]);
        }

        ##################################################################################

        if ($this->lastModified > 0)
        {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $this->lastModified) . ' GMT');
        }

        // если кэширование страницы отключено или включён режим предварительного просмотра или просмотра данных
        if (!$this->isCache || !empty($_REQUEST['_DBG_PAGE_PREVIEW']) ||
            ($_ENV['MRCORE_DEBUG'] && !empty($_REQUEST['_DBG_DATA'])))
        {
            header('Expires: Thu, 01 Jan 1970 00:00:01 GMT');
            // header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP/1.1
            // header('Pragma: no-cache'); // HTTP/1.0
        }

        // :TODO: внедрение следующих заголовков
        // header('X-Frame-Options: SAMEORIGIN');
        // header('X-XSS-Protection: 1; mode=block');
        // header('Strict-Transport-Security: max-age=43200000; includeSubDomains');

        ##################################################################################

        // если нужно сформировать тело ответа клиента
        if (null === $this->_redirectLink && 204 != $this->_codeAnswer)
        {
            // установка данных для шаблонизатора
            if (is_array($this->outputData))
            {
                // формирование документа шаблонизатором и его отображение
                $templater = &$this->getTemplater();
                $templater->assignArray($this->outputData);

                /*if ($_ENV['MRCORE_DEBUG'])
                {
                    $name = 'file';
                    $params = '';

                    if (isset($_SERVER['REQUEST_URI']))
                    {
                        $name = $_SERVER['REQUEST_URI'];

                        if (false !== ($index = strpos($name, '?')))
                        {
                            $params = substr($name, $index);
                            $name = substr($name, 0, $index);
                        }

                        $name = strtr(trim($name, '/'), '/', '-');
                    }

                    $data = &$templater->getData();
                    $filePath = MRCORE_PROJECT_DIR_TMP . 'datatpl/' . $name . '_' . sprintf('%x', crc32($params . serialize($data))) . '.php';

                    if ($fh = fopen($filePath, 'w'))
                    {
                        fwrite($fh, "<?php\n\$_templatePath = '" . $templater->getTemplate() . "';" .
                                         "\n\$_vars = " . var_export($data, true) . ";");
                        fclose($fh);
                    }
                }*/

                $templater->display();
            }
            // возвращение данных прямым способом
            else if (!empty($this->outputData))
            {
                /*__assert__*/ assert('is_string($this->outputData); // VALUE is not a string');
                echo $this->outputData;
            }
        }

        ##################################################################################

        MrEvent::add(MrEvent::TYPE_VISIT, 'HTTP ' . $this->_codeAnswer);
    }

    /**
     * Деструктор класса.
     * При установленном redirect происходит переодресация и выход из программы.
     */
    public function __destruct()
    {
        if (null !== $this->_redirectLink)
        {
            // отправка заголовка переадресации
            header('Location: ' . $this->_redirectLink->getUrl());
            // unset($this->_redirectLink);
            // unset($this->_templater);
        }
    }

}