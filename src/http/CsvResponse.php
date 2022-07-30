<?php declare(strict_types=1);
namespace mrcore\http;
use mrcore\lib\Csv;

// :TODO: добавить возможность установки разделителя
// :TODO: добавить замену разделителя в float числах
// :TODO: экранирование имени файла

/**
 * Отправка сервером запрошенного клиентом CSV файла.
 *
 * @author  Andrey J. Nazarov
 */
class CsvResponse extends HttpResponse
{
    /**
     * @inheritdoc
     */
    protected string $contentType = self::CONTENT_TYPE_CSV;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     * Подготовка указанных данных к отправке клиенту в виде CSV файла.
     *   fileName - название файла, под которым будет скачивать его клиент;
     *   csv - набор данных, который будет преобразован в CSV формат;
     *
     * @param  array  $data {@see Csv::array2csv():$data} or [fileName => string, csv => {@see Csv::array2csv():$data}]
     */
    protected function _prepareContent(string|array $data): string
    {
        $fileName = date('Y-m-d=H-i-s') . '.csv';

        if (is_array($data))
        {
            if (isset($data['fileName'], $data['csv']))
            {
                $fileName = $data['fileName'];
                $data = $data['csv'];
            }

            $data = Csv::array2csv($data);
        }

        $this->setHeader('Cache-control', 'private')
             ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
             ->setHeader('Content-Length', (string)strlen($data));

        return $data;
    }

}