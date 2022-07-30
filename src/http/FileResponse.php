<?php declare(strict_types=1);
namespace mrcore\http;
use mrcore\exceptions\HttpException;

// :TODO: экранирование имени файла

/**
 * Отправка сервером запрошенного клиентом файла.
 *
 * @author  Andrey J. Nazarov
 */
class FileResponse extends HttpResponse
{
    /**
     * @inheritdoc
     */
    protected string $contentType = self::CONTENT_TYPE_FILE;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function sendContent(): static
    {
        $out = fopen('php://output', 'wb');
        $file = fopen($this->content, 'rb');

        // :TODO: реализовать загрузку файла по частям

        stream_copy_to_stream($file, $out, null, 0);

        fclose($out);
        fclose($file);
    }

    /**
     * @inheritdoc
     * Подготовка указанного файла к отправке клиенту.
     *   fileName - название файла, под которым будет скачивать его клиент;
     *   filePath - абсолютный путь к файлу на сервере;
     *
     * @param  string|array  $data filePath or [fileName => string OPTIONAL, filePath => string]
     */
    protected function _prepareContent(string|array $data): string
    {
        $fileName = '';

        if (is_array($data))
        {
            if (isset($data['fileName']))
            {
                $fileName = $data['fileName'];
            }

            $data = $data['filePath'];
        }

        if (!is_file($data) || false === ($fileSize = filesize($data)))
        {
            throw HttpException::fileIsNotFound($data);
        }

        if ('' === $fileName)
        {
            $fileName = basename($this->content);
        }

        $this->setHeader('Cache-control', 'private')
             ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
             ->setHeader('Content-Length', (string)$fileSize);

        return $data;
    }

}