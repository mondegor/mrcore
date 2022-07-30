<?php declare(strict_types=1);
namespace mrcore\validators;

/*
    use mrcore\validators\Email;

    'email' => ['class' => Email::class]

    // OR

    use mrcore\validators\Email;

    ...

    'email' => [
        'class' => Email::class,
        'attrs' => [
            // 'multy' => false,
            // 'separator' => ',',
        ],
        'errors' => [
            Email::INVALID_VALUE => __targs('Указанный e-mail не является электронным адресом'),
            Email::INVALID_VALUES => __targs('Указанное значение должно содержать e-mail адреса разделённые знаком "%s"', 'separator'),
        ],
    ],
*/

/**
 * Валидатор e-mail адресов.
 *
 * Самый валидный e-mail по RFC:
 * {@link http://www.ex-parrot.com/~pdw/Mail-RFC822-Address.html}
 *
 * @author  Andrey J. Nazarov
 */
class Email extends StringItems
{
    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::INVALID_VALUE] = __targs('Указанный e-mail не является электронным адресом');
        $this->errors[self::INVALID_VALUES] = __targs('Указанное значение должно содержать e-mail адреса разделённые знаком "%s"', 'separator');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    protected function _validateItem(string $item): bool
    {
        // email не должен быть короче 8 символов и длинее 128 символов
        $item = trim($item);
        $length = strlen($item);

        return ($length > 7 && $length < 129 && false !== filter_var($item, FILTER_VALIDATE_EMAIL));
    }

}