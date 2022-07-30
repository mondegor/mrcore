<?php declare(strict_types=1);
namespace mrcore\mail;

/**
 * Класс для форматирования и кодирования данных необходимых при отправке письма.
 *
 * @author  Andrey J. Nazarov
 */
/*__class_static__*/ class Format
{
    /**
     * Приведение email к правильному расширенному формату с кодированием имени.
     */
    public static function formatEmail(string $email): string
    {
        $email = trim($email);

        if (false !== ($index = strpos($email, '<')) && '>' === $email[strlen($email) - 1])
        {
            return mb_encode_mimeheader(trim(substr($email, 0, $index))) . ' <' . self::stripEmail($email) . '>';
        }

        return $email;
    }

    /**
     * Из расширенного формата возвращается отдельно имя и email.
     *
     * @return  array [string, string] // [name, email]
     */
    public static function explodeEmail(string $email): array
    {
        $name = '';
        $email = trim($email);

        if (false !== ($index = strpos($email, '<')) && '>' === $email[strlen($email) - 1])
        {
            $name = trim(substr($email, 0, $index));
            $email = trim(substr($email, $index + 1, -1));
        }

        return [$name, $email];
    }

    /**
     * Формирование расширенного формата емаила.
     */
    public static function combineEmail(string $name, string $email): string
    {
        $name = trim($name);
        $email = trim($email);

        if ('' !== $name)
        {
            return $name . ' <' . $email . '>';
        }

        return $email;
    }

    /**
     * Из расширенного формата емаила выбирается только электронный адрес.
     */
    public static function stripEmail(string $email): string
    {
        $email = trim($email);

        if (false !== ($index = strpos($email, '<')) && '>' === $email[strlen($email) - 1])
        {
            return trim(substr($email, $index + 1, -1));
        }

        return $email;
    }

    /**
     * Очистка заданных emails от расширенного формата.
     */
    public static function stripEmails(string $emails): string
    {
        if ('' === $emails)
        {
            return '';
        }

        ##################################################################################

        $result = [];

        foreach (explode(',', $emails) as $email)
        {
            $result[self::stripEmail($email)] = true;
        }

        return implode(', ', array_keys($result));
    }

    /**
     * Кодирование строки используемой в письме.
     */
    public static function encode(string $value, string $charset): string
    {
        return '=?' . strtoupper($charset) . '?B?' . base64_encode($value) . '?=';
    }

    /**
     * Декодирование строки используемой в письме.
     */
    public static function decode(string $value): string
    {
        if (false !== ($index = strpos($value, '?B?')))
        {
            return base64_decode(substr($value, $index + 3, -2));
        }

        return $value;
    }

    /**
     * Проверка указанного электронного адреса.
     */
    public static function checkEmail(string $email): bool
    {
        return (false !== filter_var(self::stripEmail($email), FILTER_VALIDATE_EMAIL));
    }

}