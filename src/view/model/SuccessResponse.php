<?php declare(strict_types=1);
namespace mrcms\view\model;
use mrcms\view\ViewModelInterface;

/**
 * Модель представления - SuccessResponse.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES
 */
class SuccessResponse implements ViewModelInterface
{
    /**
     * Сообщение об успешной обработке запроса.
     */
    protected string $message = '';

    ################################### Properties ###################################

    /**
     * @inheritDoc
     */
    public function getArray(): array
    {
        return ['message' => $this->message];
    }

    /**
     * Установка нового сообщения (старое значение будет перезаписано).
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

}