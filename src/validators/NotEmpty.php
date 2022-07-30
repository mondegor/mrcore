<?php declare(strict_types=1);
namespace mrcore\validators;

/*
    use mrcore\validators\NotEmpty;

    'notempty' => ['class' => NotEmpty::class]

    // OR

    use mrcore\validators\NotEmpty;

    ...

    'notempty' => [
        'class' => NotEmpty::class,
        'attrs' => [
            'emptyValue' => null,
        ],
        'errors' => [
            NotEmpty::EMPTY_VALUE => __targs('Значение поля не может быть пустым'),
        ]
    ],
*/

/**
 * Валидатор контролирует обязательность заполнение поля.
 *
 * @author  Andrey J. Nazarov
 */
class NotEmpty extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected int $dataTypes = self::DTYPE_STRING + self::DTYPE_INT + self::DTYPE_FLOAT + self::DTYPE_ARRAY;

    /**
     * @inheritdoc
     */
    protected array $attrs = array
    (
        // :WARNING: обозначение пустого элемента должно быть не пустым,
        // иначе будет действовать стандартное правило проверки на пустоту
        'emptyValue' => null,
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::EMPTY_VALUE] = __targs('Значение поля не может быть пустым');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    protected function _validateEmpty(array $data, array &$listErrors): bool
    {
        $this->addErrorByCode(self::EMPTY_VALUE, $data, $listErrors);
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        // если задан элемент, который считается пустым
        if (!is_array($data['value']) && $data['emptyValue'] === $data['value'])
        {
            $this->addErrorByCode(self::EMPTY_VALUE, $data, $listErrors);
            return false;
        }

        return true;
    }

}