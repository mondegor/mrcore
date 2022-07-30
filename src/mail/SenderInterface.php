<?php declare(strict_types=1);
namespace mrcore\mail;

/**
 * Интерфейс для реализации отправки письма получателю.
 *
 * @author  Andrey J. Nazarov
 */
interface SenderInterface
{
    /**
     * Отправка письма получателю.
     * Реализуется интерфейс стандартной функции main.
     */
    public function mail(string $to, string $subject, string $message, string $additional_headers = null, string $additional_parameters = null): bool;

    /**
     * Возвращается ошибка, если письмо не было отправлено.
     */
    public function getError(): string;

}