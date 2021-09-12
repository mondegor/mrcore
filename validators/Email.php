<?php /* [MrLocate: group: common2v; module: validators] */
namespace mrcore\validators;

require_once 'mrcore/validators/StringItems.php';

/*
    use \mrcore\validators\Email;

    'email' => ['source' => Email::class]

    // OR

    use \mrcore\validators\AbstractValidator;
    use \mrcore\validators\Email;

    ...

    'email' => array
    (
        'source' => Email::class,
        'attrs' => array
        (
            // 'isMulty' => false,
            // 'separator' => Email::SEPARATOR,
            // 'maxLength' => 128,
        ),
        'errors' => array
        (
            AbstractValidator::INVALID_VALUE => __targs('Указанный e-mail не является электронным адресом'),
            AbstractValidator::INVALID_VALUES => __targs('E-mail адреса должны быть разделены знаком "%s"', 'separator'),
        ),
    ),
*/

/**
 * Валидатор e-mail адресов.
 *
 * Самый валидный e-mail по RFC:
 * {@link http://www.ex-parrot.com/~pdw/Mail-RFC822-Address.html}
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/validators
 */
class Email extends StringItems
{
    /**
     * @inheritdoc
     */
    /*__override__*/ public function __construct(array $attrs = [], array $errors = [])
    {
        // массив сообщений об ошибках валидатора
        // соответствующим кодам ошибок по умолчанию
        $this->_errors[self::INVALID_VALUE] = __args('1fmc2#25', 'Указанный e-mail не является электронным адресом');
        $this->_errors[self::INVALID_VALUES] = __args('1fmc2#26', 'E-mail адреса должны быть разделены знаком "%s"', 'separator');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _validateItem(string $item): bool
    {
        return (false !== filter_var(trim($item), FILTER_VALIDATE_EMAIL));
    }

}