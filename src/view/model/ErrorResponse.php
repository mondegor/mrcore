<?php declare(strict_types=1);
namespace mrcms\view\model;
use mrcms\view\ViewModelInterface;

/**
 * Модель представления - ErrorResponse.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ERROR_MESSAGE=string|array{0: string, 1: string} // 0 - имя поля ошибки, 1 - сообщение об ошибке
 */
class ErrorResponse implements ViewModelInterface
{
    /**
     * Название поле по умолчанию, в котором произошла ошибка.
     */
    private string $fieldName = 'syserror';

    /**
     * Список ошибок.
     *
     * @var  T_ERROR_MESSAGE[]
     */
    protected array $errors = [];

    ################################### Properties ###################################

    /**
     * Установка названия поля по умолчанию, в котором произошла ошибка.
     */
    public function setFieldName(string $name): static
    {
        $this->fieldName = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getArray(): array
    {
        return ['errors' => $this->errors];
    }

    /**
     * Добавление новой ошибки в конец массива.
     *
     * @param  T_ERROR_MESSAGE $message
     */
    public function addError(string|array $message): static
    {
        if (is_string($message))
        {
            $message = [$this->fieldName, $message];
        }

        $this->errors[] = $message;

        return $this;
    }

}