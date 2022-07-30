<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\web\PathToAction;
use mrcore\base\TraitSingleton;
use mrcore\http\ResponseInterface;


/**
 * Сервис управления шаблонизаторами.
 */
class ResponseService implements ServiceInterface
{
    use TraitSingleton;


    #################################### Methods #####################################

//'' => $_ENV['MRAPP_HTTP_VERSION'],
//'' => $_ENV['MRAPP_HTTP_CONTENT_TYPE'],
//'' => $_ENV['MRAPP_HTTP_CHARSET'],
    public function __construct(private string $httpVersion, private string $contentType, private string $charset)
    {
        $this->_initSingleton();
    }

    public function getContentType(): string
    {

    }

    public function getCharset(): string
    {
        return $this->languages[$this->curLanguage][3] ?? 'UTF-8'; // 3 - charset
    }

    public function getStatusText(int $statusCode): string
    {
        return ResponseInterface::STATUS_TEXTS[$statusCode] ?? 'Unknown error';
    }

    /**
     * Возвращается ответ сервера для отправки ошибки.
     */
    public function createResponse(string $class, int $statusCode = null): ResponseInterface
    {

    }


    public function getShort()
    {
        return ltrim(strstr($response->getContentType(), '/'), '/');
    }

}