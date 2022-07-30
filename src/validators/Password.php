<?php declare(strict_types=1);
namespace mrcore\validators;
use mrcore\lib\Crypt;

/*
    use mrcore\validators\Password;

    'password' => ['class' => Password::class, 'pattern' => Password::PATTERN_*]

    // OR

    use mrcore\validators\Password;

    ...

    'password' => [
        'class' => Password::class,
        'attrs' => [
            'pattern' => Password::PATTERN_*,
        ],
        'errors' => [
            Password::INVALID_VALUE => __targs('Указанный пароль содержит недопустимые символы'),
            Password::INVALID_SPECIAL => __targs('Указанный пароль слишком прост'),
        ],
    ],
*/

/**
 * Валидатор сложности паролей.
 *
 * @author  Andrey J. Nazarov
 */
class Password extends AbstractValidator
{
    /**
     * Шаблоны различных паролей.
     */
    public const PATTERN_PASSWORD = '^[a-zA-Z0-9!@#$%^&*_\\-+=;:,.?]+$',
                 PATTERN_PASSWORD_az_AZ_09 = '^[a-zA-Z0-9]+$';

    ################################### Properties ###################################

    /**
     * @inheritdoc
     */
    protected int $dataTypes = self::DTYPE_STRING;

    /**
     * @inheritdoc
     */
    protected array $attrs = array
    (
        'pattern' => self::PATTERN_PASSWORD,
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::INVALID_VALUE] = __targs('Указанный пароль содержит недопустимые символы');
        $this->errors[self::INVALID_SPECIAL] = __targs('Указанный пароль слишком прост');

        parent::__construct($attrs, $errors);

        assert(is_string($this->attrs['pattern']));
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        if (preg_match('/' . $data['pattern'] . '/', $data['value']) < 1)
        {
            $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
            return false;
        }

        if (Crypt::STRENGTH_NOT_RATED === $this->_getPasswordStrength($data['value']))
        {
            $this->addErrorByCode(self::INVALID_SPECIAL, $data, $listErrors);
            return false;
        }

        return true;
    }

    ##################################################################################

    protected function _getPasswordStrength(string $value): int
    {
        return Crypt::getPasswordStrength($value);
    }

}