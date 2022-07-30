<?php declare(strict_types=1);
namespace mrcore\http;

/**
 * HTTP ответ сервера.
 *
 * @author  Andrey J. Nazarov
 */
class HttpResponse implements ResponseInterface
{
    /**
     * Версия протокола используемая в ответе сервера.
     */
    protected string $protocolVersion;
    /**
     * Тип тела используемый в ответе сервера.
     *
     * @see ResponseInterface::CONTENT_TYPE_HTML
     */
    protected string $contentType;

    /**
     * Кодировка ответа сервера.
     */
    protected string $charset;

    /**
     * Код ответа сервера.
     *
     * @see ResponseInterface::HTTP_OK
     */
    protected int $statusCode;

    /**
     * Заголовки ответа сервера.
     *
     * @var  array [[string, string|string[]], ...] // [[headerName, headerValue|headerValues], ...]
     */
    protected array $headers = [];

    /**
     * Тело ответа сервера.
     */
    protected string $content = '';

    #################################### Methods #####################################

    public function __construct(string $protocolVersion, string $contentType = null, string $charset = null, int $statusCode = null)
    {
        if (null === $protocolVersion)
        {
            $this->protocolVersion = '2.0';
        }

        if (null === $contentType)
        {
            $this->contentType = self::CONTENT_TYPE_HTML;
        }

        if (null === $charset)
        {
            $this->contentType = 'UTF-8';
        }

        if (null === $statusCode)
        {
            $this->statusCode = self::HTTP_OK;
        }
    }

    /**
     * @inheritdoc
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritdoc
     */
    public function setProtocolVersion(string $version): static
    {
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @inheritdoc
     */
    public function setContentType(string $contentType): static
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @inheritdoc
     */
    public function setCharset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritdoc
     */
    public function setStatusCode(int $statusCode): static
    {
        assert($statusCode >= 100 && $statusCode <= 599);

        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHeader(string $name): string|array|null
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setHeader(string $name, string|array $value): static
    {
        assert(!is_array($value) || array_is_list($value));

        if (isset($this->headers[$name]))
        {
            if (is_string($this->headers[$name]))
            {
                $this->headers[$name] = [$this->headers[$name]];
            }

            if (is_array($value))
            {
                $this->headers[$name] = array_merge($this->headers[$name], $value);
            }
            else
            {
                $this->headers[$name][] = $value;
            }
        }
        else
        {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeHeader(string $name): static
    {
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @inheritdoc
     */
    public function setContent(string|array $data): static
    {
        $this->content = $this->_prepareContent($data);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRedirect(string $location = null): bool
    {
        return in_array($this->statusCode, [201, 301, 302, 303, 307, 308]) &&
               (null === $location || $location === $this->getHeader('Location'));
    }

    /**
     * @inheritdoc
     */
    public function sendHeaders(): static
    {
        if (headers_sent())
        {
            return $this;
        }

        assert(self::HTTP_NO_CONTENT !== $this->statusCode || '' === $this->content, 'С установленным кодом ответа 204 тело запроса должно быть пустым');
        assert($this->statusCode < 200 || $this->statusCode > 203 || '' !== $this->content, sprintf('С установленным кодом ответа %u тело запроса должно быть указано', $this->statusCode));

        foreach ($this->headers as $name => $values)
        {
            if (is_string($values))
            {
                $values = [$values];
            }

            foreach ($values as $value)
            {
                header($name . ': ' . $value, false, $this->statusCode);
            }
        }

        header(sprintf('Content-Type: %s; charset=%s', $this->contentType, strtoupper($this->charset)), true, $this->statusCode);
        header(sprintf('HTTP/%s %s %s', $this->protocolVersion, $this->statusCode, self::STATUS_TEXTS[$this->statusCode]), true, $this->statusCode);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function sendContent(): static
    {
        echo $this->content;
    }

    /**
     * @inheritdoc
     */
    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function closeConnection(): static
    {
        if (function_exists('fastcgi_finish_request'))
        {
            fastcgi_finish_request();
        }

        return $this;
    }

    /**
     * Преобразования указанных данных в строку.
     */
    protected function _prepareContent(string|array $data): string
    {
        return $data;
    }

}