<?php declare(strict_types=1);
namespace mrcore\http;

/**
 * HTTP ответ сервера в виде JSON объекта.
 *
 * @author  Andrey J. Nazarov
 */
class JsonResponse extends HttpResponse
{
    /**
     * @inheritdoc
     */
    protected string $contentType = self::CONTENT_TYPE_JSON;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    protected function _prepareContent(string|array $data): string
    {
        return json_encode($data);
    }

}
