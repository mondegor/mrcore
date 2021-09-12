<?php declare(strict_types=1);
namespace mrcore\base;
use Closure;

/**
 * Класс BuilderLink построен на основе паттерна Value Object.
 * Он используется при строительстве URL-ов в котором
 * принимают участие сразу несколько компонентов, и которые
 * не знают и не должны знать ничего друг о друге.
 *
 * Пример URL-а: [scheme] + [host] + [path] + [path:last] + [query] + [fragment]
 *
 * ([domain] = [scheme] + [host])
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/base
 */
class BuilderLink
{
    /**
     * Название протокола.
     * Общий вид: [scheme].
     *
     * @var    string
     */
    public string $scheme;

    /**
     * Название хоста.
     * Общий вид: [host].
     *
     * @var    string
     */
    public string $host;

    /**
     * Путь к файлу на сервере.
     * Общий вид: [path].
     *
     * @var    array
     */
    public array $path = [];

    /**
     * Название файла на сервере.
     * Общий вид: [path:last].
     *
     * @var    string
     */
    public string $file = '';

    /**
     * Callback функция вызываемая внутри вызова метода getUrl.
     * Параметры: 0 - path, 1 - file, 2 - args;
     *
     * @var    Closure
     */
    public ?Closure $cbUrl;

    /**
     * Якорь ([fragment]) указывает на конкретную
     * часть страницы.
     *
     * @var    string
     */
    protected string $_anchor = '';

    /**
     * Ассоциативный массив параметров, из которого
     * формируется запрос ([query]) к скрипту находящемуся на сервере.
     *
     * @var    array
     */
    protected array $_args;

    #################################### Methods #####################################

    /**
     * Создание объекта BuilderLink на основе указанного url.
     *
     * @param      string  $url
     * @return     BuilderLink
     */
    public static function &factory(string $url): BuilderLink
    {
        $parsed = @parse_url($url); // :WARNING: заглушены ошибки в функции
        $args = array();

        if (!empty($parsed['query']))
        {
            parse_str($parsed['query'], $args);
        }

        $result = new static // BuilderLink
        (
            empty($parsed['host']) ? '' : $parsed['host'],
            empty($parsed['path']) ? '' : $parsed['path'],
            $args,
            empty($parsed['scheme']) ? 'http' : $parsed['scheme']
        );

        if (!empty($parsed['fragment']))
        {
            $result->setAnchor($parsed['fragment']);
        }

        return $result;
    }

    ##################################################################################

    /**
     * Конструктор класса.
     *
     * @param      string  $host
     * @param      string  $path - путь к файлу на сервере
     * @param      array  $args OPTIONAL - фссоциативный массив параметров
     * @param      string  $scheme OPTIONAL
     * @param      Closure  $cbUrl OPTIONAL - сallback функция вызываемая внутри вызова метода getUrl
     */
    public function __construct(string $host, string $path, array $args = [], string $scheme = 'http', Closure $cbUrl = null)
    {
        $this->scheme = $scheme;
        $this->host = $host;

        if (!empty($path))
        {
            $this->path = explode('/', $path);

            // последний элемент пути является названием файла или пустой строкой
            $this->file = array_pop($this->path);
        }

        $this->_args = $args;
        $this->cbUrl = $cbUrl;
    }

    /**
     * Добавление строки к пути.
     * В параметре $path можно добавить несколько путей через /, но они будут записаны одним элементом.
     * Т.е. если сразу после вызвать метод pop(), то вернется строка состоящая из тех же путей.
     *
     * @param      string  $path
     * @return     BuilderLink
     */
    public function &push(string $path): BuilderLink
    {
        $this->path[] = $path;

        return $this;
    }

    ///**
    // * Выборка последней строки из пути с удалением.
    // *
    // * @param      string|null
    // * @return     BuilderLink
    // */
    //public function &pop(string &$result = null): BuilderLink
    //{
    //    $result = null;
    //
    //    if (!empty($this->path))
    //    {
    //        $result = array_pop($this->path);
    //    }
    //
    //    return $this;
    //}

    /**
     * Получение параметра по имени.
     *
     * @param      string  $name - название параметра
     * @return     mixed  $value (scalar|array) - значение параметра
     */
    public function get(string $name)
    {
        return $this->_args[$name] ?? null;
    }

    /**
     * Установка параметра.
     *
     * @param      string  $name - название параметра
     * @param      mixed  $value (scalar|array) - значение параметра
     * @return     BuilderLink
     */
    public function &set(string $name, $value): BuilderLink
    {
        assert(is_scalar($value) || is_array($value));

        $this->_args[$name] = $value;

        return $this;
    }

    /**
     * Удаление параметра или массива параметров.
     *
     * @param      string  $name - название параметра
     * @return     BuilderLink
     */
    public function &remove(string $name): BuilderLink
    {
        foreach ((array)$name as $key)
        {
            unset($this->_args[$key]);
        }

        return $this;
    }

    /**
     * Добавление ассоциативного массива параметров.
     *
     * Пример: array(key1 => value1, key2 => value2,...);
     *
     * @param      array  $args - ассоциативный массив параметров
     * @return     BuilderLink
     */
    public function &addRange(array $args): BuilderLink
    {
        $this->_args = array_replace($this->_args, $args);

        return $this;
    }

    /**
     * Установка якоря.
     *
     * @param      string  $anchor - якорь (указывает на конкретную часть страницы)
     * @return     BuilderLink
     */
    public function &setAnchor(string $anchor): BuilderLink
    {
        $this->_anchor = $anchor;

        return $this;
    }

    /**
     * Возвращение сформированного URL в виде строки.
     *
     * @param      bool  $addHost OPTIONAL - добавлять в ссылку название хоста
     * @return     string
     */
    public function getUrl(bool $addHost = true): string
    {
        $host = $addHost ? $this->host : '';
        $path = $this->path;
        $file = $this->file;
        $args = $this->_args;

        if (null !== $this->cbUrl)
        {
            $cbUrl = &$this->cbUrl;
            $cbUrl($path, $file, $args);
        }

        ##################################################################################

        // если хост указан, то протокол задаётся явно
        return ('' === $host ? (empty($path) ? '' : implode('/', $path) . '/') :
                               $this->scheme . '://' . $host . implode('/', $path) . '/') .
               $file .
               (empty($args) ? '' : '?' . http_build_query($args)) .
               ('' === $this->_anchor ? '' : '#' . $this->_anchor);
    }

}