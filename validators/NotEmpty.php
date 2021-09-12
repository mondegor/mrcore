<?php /* [MrLocate: group: common2v; module: validators] */
namespace mrcore\validators;

require_once 'mrcore/validators/AbstractValidator.php';

/*
    'notempty' => ['source' => NotEmpty::class]

    // OR

    use \mrcore\validators\AbstractValidator;

    ...

    'notempty' => array
    (
        'source' => NotEmpty::class,
        'attrs' => array
        (
            'emptyValue' => null,
        ),
        'errors' => array
        (
            AbstractValidator::EMPTY_VALUE => __targs('Значение поля не может быть пустым'),
        )
    ),
*/

/**
 * Валидатор контролирует обязательность заполнение поля.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/validators
 */
class NotEmpty extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    /*__override__*/ protected array $_attrs = array
    (
        // :WARNING: обозначение пустого элемента должно быть не пустым,
        // иначе будет действовать стандартное правило проверки на пустоту
        'emptyValue' => null,
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ public function __construct(array $attrs = [], array $errors = [])
    {
        // массив сообщений об ошибках валидатора
        // соответствующим кодам ошибок по умолчанию
        $this->_errors[self::EMPTY_VALUE] = __args('1fmc2#2s', 'Значение поля не может быть пустым');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _validate(array $data, array &$listErrors): bool
    {
        // если значение является абсолютно пустым (не содержит символа "0" или 0, false)
        if (empty($data['value']) && (null === $data['value'] || '' === $data['value'] || is_array($data['value'])))
        {
            $this->addErrorByCode(self::EMPTY_VALUE, $data, $listErrors);
            return false;
        }

        // если задан элемент, который считается пустым
        if ($this->_getAttr('emptyValue', $data) === $data['value'])
        {
            $this->addErrorByCode(self::EMPTY_VALUE, $data, $listErrors);
            return false;
        }

        return true;
    }

}