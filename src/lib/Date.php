<?php declare(strict_types=1);
namespace mrcore\lib;

/**
 * Проверка на соответствие значения с заданным шаблоном,
 * а также проверка длины значения.
 *
 * При валидации используется формат в $_attrs['format'],
 * но при формировании сообщения об ошибке выводится дата в формате $_attrs['outputFormat']
 * Сделано для того, чтобы в браузере пользователь заполнял дату в $_attrs['outputFormat'],
 * js приводил к формату $_attrs['format'] и отправлял это значение на сервер.
 *
 * @author  Andrey J. Nazarov
 */
/*__class_static__*/ class Date
{
    /**
     * Константы формата даты ISO используемые в функции date().
     */
    const DATE_EU  = 'd.m.Y',
          DATE_US  = 'm/d/Y',
          DATE_ISO = 'Y-m-d';

    #################################### Methods #####################################

    /**
     * Проверка даты на соответствие указанному формату.
     *
     * @param      string  $date
     * @param      string  $format
     * @return     bool
     */
    public static function isValidDate(string $date, string $format): bool
    {
        return ('' !== $date && false !== ($date = self::parseDate($date, $format)));
    }

    /**
     * Конвертация даты из одного формата в другой.
     *
     * @param      string  $convertDate
     * @param      string  $fromFormat
     * @param      string  $toFormat
     * @return     string
     */
    public static function convertDate(string $convertDate, string $fromFormat, string $toFormat): string
    {
        $result = $convertDate;

        if ($fromFormat != $toFormat && '' != $convertDate)
        {
            if (false !== ($date = self::parseDate($convertDate, $fromFormat)))
            {
                $result = ($date['year'] > 1970) ?
                              date($toFormat, mktime(0, 0, 0, $date['month'], $date['day'], $date['year'])) :
                              str_replace(array('d', 'm', 'Y'), array(str_pad($date['day'], 2, '0', STR_PAD_LEFT), str_pad($date['month'], 2, '0', STR_PAD_LEFT), $date['year']), $toFormat);
            }
            else
            {
                trigger_error(sprintf('Конвертация даты "%s" из формата "%s" в формат "%s" невозможен.', $convertDate, $fromFormat, $toFormat), E_USER_WARNING);
            }
        }

        return $result;
    }

    /**
     * Разбор даты в соответствие с указанным форматом.
     * Метод не проверяет правильность значений дня, месяца, года.
     * Это делает метод {@link Date::isValidDate}
     * В случае успешного разбора строки возвращается массив:
     * array
     * (
     *     'day'   => 15,
     *     'month' => 4,
     *     'year'  => 2007,
     * );
     *
     * @param      string  $date
     * @param      string  $format
     * @return     array|false [day => int, month => int, year => int]
     */
    public static function parseDate(string $date, string $format)
    {
        switch ($format)
        {
            case self::DATE_EU:
                $arr = explode('.', $date, 3);

                if (3 === count($arr) && checkdate((int)$arr[1], (int)$arr[0], (int)$arr[2]))
                {
                    return array
                    (
                        'day'   => (int)$arr[0],
                        'month' => (int)$arr[1],
                        'year'  => (int)$arr[2],
                    );
                }
                break;

            case self::DATE_US:
                $arr = explode('/', $date, 3);

                if (3 === count($arr) && checkdate((int)$arr[0], (int)$arr[1], (int)$arr[2]))
                {
                    return array
                    (
                        'day'   => (int)$arr[1],
                        'month' => (int)$arr[0],
                        'year'  => (int)$arr[2],
                    );
                }
                break;

            case self::DATE_ISO:
                $arr = explode('-', $date, 3);

                if (3 === count($arr) && checkdate((int)$arr[1], (int)$arr[2], (int)$arr[0]))
                {
                    return array
                    (
                        'day'   => (int)$arr[2],
                        'month' => (int)$arr[1],
                        'year'  => (int)$arr[0],
                    );
                }
                break;

            default:
                trigger_error(sprintf('Задан неизвестный формат даты "%s"', $format), E_USER_WARNING);
                break;
        }

        return false;
    }

    /**
     * Возвращается дата на основе шаблона.
     * Если шаблон неверный, то возвращается значение
     * $pattern. (т.е. если, например, явно указать 2007-02-07,
     * то вернётся это значение без изменения)
     *
     * В шаблоне может участвовать только одно из указанных значений:
     * d  - текущая дата
     * m  - первый день текущего месяца текущего года
     * mu - последний день текущего месяца текущего года
     * y  - первый день первого месяца текущего года
     * yu - последний день последнего месяца текущего года
     *
     * Далее могут идти один из знаков +/-, далее количестсво
     * прибавляемых или отнимаемых дней, месяцев, лет
     * (в зависимости от d, m, y).
     *
     * @param      string  $pattern
     * @return     string
     */
    public static function getDateFromPattern(string $pattern): string
    {
        if (preg_match('/^([dmy]u?)(?(?=[\+\-])([\+\-])([0-9]+))$/', $pattern, $m) > 0)
        {
            $pts = array
            (
                'd'  => 'Y-m-d',
                'm'  => 'Y-m-01',
                'mu' => 'Y-m-t',
                'y'  => 'Y-01-01',
                'yu' => 'Y-12-31',
            );

            if (count($m) > 2)
            {
                $curDate = explode('-', date('Y-m-d'));
                $curDate[('d' === $m[1][0] ? 2 : ('m' === $m[1][0] ? 1 : 0))] += (('+' === $m[2]) ? (int)$m[3] : -1 * (int)$m[3]);

                if ($curDate[0] > 1970)
                {
                    return date($pts[$m[1]], mktime(0, 0, 0, (int)$curDate[1], (int)$curDate[2], (int)$curDate[0]));
                }

                $year = 2000 + ($curDate[0] % 4);
                $date = date($pts[$m[1]], mktime(0, 0, 0, (int)$curDate[1], (int)$curDate[2], $year));

                return str_pad($curDate[0] + (substr($date, 0, 4) - $year), 4, STR_PAD_LEFT) . substr($date, 4);
            }

            return date($pts[$m[1]]);
        }

        return $pattern;
    }

}