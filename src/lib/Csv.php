<?php declare(strict_types=1);
namespace mrcore\lib;

/**
 * Библиотека объединяющая методы работы с CSV форматом.
 *
 * @author  Andrey J. Nazarov
 */
/*__class_static__*/ class Csv
{
    /**
     * Указанный массив $data преобразуется в CSV формат.
     *
     * @param  array  $data [[string|string[], ...]] or [string|string[], ...]
     */
    public static function array2csv(array $data, string $delimiter = ';', string $decpoint = ','): string
    {
        assert(array_is_list($data));

        if (1 === count($data))
        {
            $data = array_pop($data);
        }

        if (!is_array($data))
        {
            return (string)$data;
        }

        ##################################################################################

        $buffer = fopen('php://memory', 'rb+');
        $first = true;

        foreach ($data as &$row)
        {
            if (!is_array($row))
            {
                $row = [(string)$row];
            }

            // из первого элемента формируется "шапка"
            if ($first)
            {
                fputcsv($buffer, array_keys($row), $delimiter);
                $first = false;
            }

            // преобразование . в , в числах с плавающей запятой
            if ('.' !== $decpoint)
            {
                foreach ($row as &$item)
                {
                    if (preg_match('/^(?(?=-?\d+\.\d+)-?\d+\.\d+|-?\d+)$/', $item) > 0)
                    {
                        $item = strtr($item, '.', $decpoint);
                    }
                }
            }

            fputcsv($buffer, $row, $delimiter);
        }

        rewind($buffer);
        $result = stream_get_contents($buffer);
        fclose($buffer);

        return $result;
    }

}