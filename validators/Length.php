<?php /* [MrLocate: group: common2v; module: validators] */
namespace mrcore\validators;

require_once 'mrcore/validators/AbstractValidator.php';

/*
    'length' => ['source' => Length::class]

    // OR

    use \mrcore\validators\AbstractValidator;

    ...

    'length' => array
    (
        'source' => Length::class,
        'attrs' => array
        (
            // 'minLength' => 0,
            // 'maxLength' => 1000,
        ),
        'errors' => array
        (
            AbstractValidator::INVALID_LENGTH => __targs('Значение должно быть не короче %d символов и не длиннее %d символов', 'minLength', 'maxLength'),
            // AbstractValidator::INVALID_LENGTH_MIN => __targs('Значение должно быть не короче %d символов', 'minLength'),
            // AbstractValidator::INVALID_LENGTH_MAX => __targs('Значение должно быть не длиннее %d символов', 'maxLength'),
        ),
    ),
*/

/**
 * Валидатор значений.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/validators
 */
class Length extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    /*__override__*/ protected array $_attrs = array
    (
        'showFullError' => false, // отображать сообщение INVALID_LENGTH если указаны и minLength и maxLength
        'minLength' => 0,
        'maxLength' => 0,
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ public function __construct(array $attrs = [], array $errors = [])
    {
        // массив сообщений об ошибках валидатора
        // соответствующим кодам ошибок по умолчанию
        $this->_errors[self::INVALID_LENGTH]     = __args('1fmc2#2j', 'Значение должно быть не короче %d символов и не длиннее %d символов', 'minLength', 'maxLength');
        $this->_errors[self::INVALID_LENGTH_MIN] = __args('1fmc2#2k', 'Значение должно быть не короче %d символов', 'minLength');
        $this->_errors[self::INVALID_LENGTH_MAX] = __args('1fmc2#2l', 'Значение должно быть не длиннее %d символов', 'maxLength');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _validate(array $data, array &$listErrors): bool
    {
        // если строка является точно не пустой
        if (($length = /*ok*/mb_strlen($data['value'])) < 1)
        {
            return true;
        }

        $minLength = $this->_getAttr('minLength', $data);
        $maxLength = $this->_getAttr('maxLength', $data);

        assert(0 === $maxLength || $minLength <= $maxLength);

        if ($maxLength > 0)
        {
            // если необходимо точное соответствие длины
            if ($minLength === $maxLength && $length !== $maxLength)
            {
                $this->addErrorByCode(self::INVALID_LENGTH, $data, $listErrors);
                return false;
            }

            if ($length > $maxLength)
            {
                $this->addErrorByCode($this->_attrs['showFullError'] && $minLength > 0 ? self::INVALID_LENGTH : self::INVALID_LENGTH_MAX, $data, $listErrors);
                return false;
            }
        }

        if ($length < $minLength)
        {
            $this->addErrorByCode($this->_attrs['showFullError'] && $maxLength > 0 ? self::INVALID_LENGTH : self::INVALID_LENGTH_MIN, $data, $listErrors);
            return false;
        }

        return true;
    }

}