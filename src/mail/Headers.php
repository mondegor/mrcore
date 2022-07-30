<?php declare(strict_types=1);
namespace mrcore\mail;

/**
 * Контейнер заголовков письма.
 *
 * @author  Andrey J. Nazarov
 */
class Headers
{
    /**
     * Ассоциативный массив заголовков.
     *
     * @var   array map[string]string
     */
    protected array $headers = [];

    #################################### Methods #####################################

    /**
     * @param  array|null $headers {@see Headers::$headers}
     */
    public function __construct(array $headers = null)
    {
        if (null !== $headers)
        {
            $this->setHeaders($headers);
        }
    }

    ///**
    // * Проверка установленности заголовка по его имени.
    // */
    //public function isExists(string $name): bool
    //{
    //    return isset($this->headers[$name]);
    //}

    /**
     * Добавление/изменение заголовка.
     */
    public function setHeader(string $name, string $value): Headers
    {
        $this->headers[$name] = trim($value);

        return $this;
    }

    /**
     * Возвращается заголовок по его имени.
     */
    public function getHeader(string $name): string
    {
        return $this->headers[$name] ?? '';
    }

    /**
     * Добавление заголовков.
     *
     * @param  array  $headers {@see Headers::$headers}
     */
    public function setHeaders(array $headers): Headers
    {
        foreach ($headers as $name => $value)
        {
            $this->headers[$name] = trim($value);
        }

        return $this;
    }

     ///**
     // * Удаление заголовка по его имени.
     // */
     //public function remove(string $name): Headers
     //{
     //    unset($this->headers[$name]);
     //
     //    return $this;
     //}

     ///**
     // * Полное удаление заголовков контейнера.
     // */
     //public function clear(): Headers
     //{
     //    $this->headers = [];
     //
     //    return $this;
     //}

    /**
     * Возвращается строка отображения заголовков.
     */
    public function toString(): string
    {
        $result = '';

        foreach ($this->headers as $name => $body)
        {
            if ('' === $body)
            {
                continue;
            }

            $result .= $name . ': ' . $body . "\r\n";
        }

        return $result;
    }

}