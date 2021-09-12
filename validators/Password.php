<?php /* [MrLocate: group: common2v; module: validators] */
namespace mrcore\validators;
use mrcore\lib\Crypt;

require_once 'mrcore/lib/Crypt.php';
require_once 'mrcore/validators/AbstractValidator.php';

/*
    'password' => ['source' => Password::class, 'pattern' => Password::PATTERN_*]

    // OR

    use \mrcore\lib\Crypt;
    use \mrcore\validators\AbstractValidator;
    use \mrcore\validators\Password;

    ...

    'password' => array
    (
        'source' => Password::class,
        'attrs' => array
        (
            'pattern' => Password::PATTERN_*,
        ),
        'errors' => array
        (
            AbstractValidator::INVALID_VALUE => __targs('Указанный пароль содержит недопустимые символы'),
            AbstractValidator::INVALID_SPECIAL => __targs('Указанный пароль слишком прост'),
        ),
    ),
*/

/**
 * Валидатор сложности паролей.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/validators
 */
class Password extends AbstractValidator
{
    /**
     * Шаблоны различных паролей.
     */
    public const PATTERN_PASSWORD = '^[a-zA-Z0-9!#$%&()*+,\\-.\\:;=@\\^_`~]+$',
                 PATTERN_PASSWORD_az_AZ_09 = '^[a-zA-Z0-9]+$';

    ################################### Properties ###################################

    /**
     * Произвольные атрибуты компонента.
     *
     * @param      array
     */
    /*__override__*/ protected array $_attrs = array
    (
        'pattern' => self::PATTERN_PASSWORD,
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ public function __construct(array $attrs = [], array $errors = [])
    {
        // массив сообщений об ошибках валидатора
        // соответствующим кодам ошибок по умолчанию
        $this->_errors[self::INVALID_VALUE] = __args('1fmc2#2x', 'Указанный пароль содержит недопустимые символы');
        $this->_errors[self::INVALID_SPECIAL] = __args('1fmc2#2y', 'Указанный пароль слишком прост');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _validate(array $data, array &$listErrors): bool
    {
        if ('' === $data['value'])
        {
            return true;
        }

        if (preg_match('/' . $this->_getAttr('pattern', $data) . '/', $data['value']))
        {
            if (Crypt::STRENGTH_NOT_RATED === $this->_getPasswordStrength($data['value']))
            {
                $this->addErrorByCode(self::INVALID_SPECIAL, $data, $listErrors);
                return false;
            }
        }
        else
        {
            $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
            return false;
        }

        return true;
    }

    /**
     * @see    Crypt::getPasswordStrength()
     */
    /*__private__*/protected function _getPasswordStrength(string $value): int
    {
        return Crypt::getPasswordStrength($value);
    }

}