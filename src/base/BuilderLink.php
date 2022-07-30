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
 * @author  Andrey J. Nazarov
 */
class BuilderLink
{
    /**
     * Название протокола.
     * Общий вид: [scheme].
     */
    public string $scheme = 'https';

    /**
     * Название хоста.
     * Общий вид: [host].
     */
    public string $host;

    /**
     * Путь к файлу на сервере.
     * Общий вид: [path].
     *
     * @var    string[]
     */
    public array $path = [];

    /**
     * Название файла на сервере.
     * Общий вид: [path:last].
     */
    public string $file = '';

    /**
     * Callback функция вызываемая внутри вызова метода getUrl.
     *
     * @var     Closure|null  (function (string $path, string $file, array $args): void)
     */
    public ?Closure $cbUrl;

    /**
     * Ассоциативный массив параметров, из которого
     * формируется запрос ([query]) к скрипту находящемуся на сервере.
     *
     * @var    array [string => mixed, ...]
     */
    protected array $args = [];

    /**
     * Якорь ([fragment]) указывает на конкретную часть страницы.
     */
    public string $anchor = '';

    #################################### Methods #####################################

    /**
     * Создание объекта BuilderLink на основе указанного url.
     *
     * @param      string  $url
     * @return     BuilderLink
     */
    public static function factory(string $url): BuilderLink
    {
        $parsed = @parse_url($url); // :WARNING: заглушены ошибки в функции
        $args = [];

        if (!empty($parsed['query']))
        {
            parse_str($parsed['query'], $args);
        }

        $result = new static // BuilderLink
        (
            empty($parsed['host']) ? '' : $parsed['host'],
            empty($parsed['path']) ? '' : $parsed['path'],
            $args,
            empty($parsed['scheme']) ? null : $parsed['scheme']
        );

        if (!empty($parsed['fragment']))
        {
            $result->anchor = $parsed['fragment'];
        }

        return $result;
    }

    ##################################################################################

    /**
     * Конструктор класса.
     *
     * @param  string       $host
     * @param  string       $path
     * @param  array        $args [string => mixed, ...]
     * @param  string|null  $scheme
     * @param  Closure|null $cbUrl
     */
    private function __construct(string $host, string $path, array $args, string $scheme = null, Closure $cbUrl = null)
    {
        if (null !== $scheme)
        {
            $this->scheme = $scheme;
        }

        $this->host = $host;

        if ('' !== ($path = trim($path, '/')))
        {
            $this->path = explode('/', $path);

            // последний элемент пути является названием файла или пустой строкой
            $this->file = array_pop($this->path);
        }

        $this->args = $args;
        $this->cbUrl = $cbUrl;
    }

    /**
     * Добавление строки к пути.
     * В параметре $path можно добавить несколько путей через /, но они будут записаны одним элементом.
     *
     * @param      string  $path
     * @return     BuilderLink
     */
    public function push(string $path): BuilderLink
    {
        $this->path[] = $path;

        return $this;
    }

    /**
     * Возвращается значение параметра по имени.
     *
     * @param  string $name
     * @return string|string[]|null
     */
    public function get(string $name): string|array|null
    {
        return $this->args[$name] ?? null;
    }

    /**
     * Установка параметра.
     *
     * @param  string|string[]  $value
     */
    public function set(string $name, string|array $value): BuilderLink
    {
        $this->args[$name] = $value;

        return $this;
    }

    /**
     * Удаление параметра или массива параметров.
     *
     * @param      string  $name
     * @return     BuilderLink
     */
    public function remove(string $name): BuilderLink
    {
        foreach ((array)$name as $key)
        {
            unset($this->args[$key]);
        }

        return $this;
    }

    /**
     * Добавление ассоциативного массива параметров.
     *
     * @param      array  $args [string => string|array, ...]
     * @return     BuilderLink
     */
    public function addRange(array $args): BuilderLink
    {
        $this->args = array_replace($this->args, $args);

        return $this;
    }

    /**
     * Возвращается сформированный URL в виде строки.
     *
     * @param      bool  $addHost (добавлять в ссылку название хоста)
     * @return     string
     */
    public function getUrl(bool $addHost = true): string
    {
        $host = $addHost ? $this->host : '';
        $path = $this->path;
        $file = $this->file;
        $args = $this->args;

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
               ('' === $this->anchor ? '' : '#' . $this->anchor);
    }

}