<?php declare(strict_types=1);
namespace mrcore\http;
use mrcore\exceptions\CoreException;

// :TODO: добавить возможность установки разделителя
// :TODO: добавить замену разделителя в float числах
// :TODO: экранирование имени файла

/**
 * HTTP ответ сервера редиректом на указанный ресурс.
 *
 * @author  Andrey J. Nazarov
 */
class RedirectResponse extends HttpResponse
{
    /**
     * @inheritdoc
     */
    protected string $contentType = self::CONTENT_TYPE_TEXT;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     * Подготовка указанных данных к отправке клиенту в виде CSV файла.
     */
    protected function _prepareContent(string|array $data): string
    {
        assert(is_string($data));

        if (!$this->isRedirect())
        {
            throw CoreException::httpStatusCodeIsNotRedirect($this->statusCode);
        }

        $this->setHeader('Location', $data);

        return '';
    }

}