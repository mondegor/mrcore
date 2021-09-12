<?php /* [MrLocate: group: common2v; module: validators] */
namespace mrcore\validators;

require_once 'mrcore/validators/AbstractValidator.php';

/*
   'value' => ['source' => Value::class, 'pattern' => Value::PATTER_*]

    // OR

    use \mrcore\validators\AbstractValidator;
    use \mrcore\validators\Value;

    ...

    'value' => array
    (
        'source' => Value::class,
        'attrs' => array
        (
            'pattern' => Value::PATTER_*,
        ),
        'errors' => array
        (
            AbstractValidator::INVALID_VALUE => __targs('Значение не соответствует формату "%s"', 'pattern'),
        ),
    ),
*/

/**
 * Проверка на соответствие значения с заданным шаблоном,
 * а также проверка длины значения.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/validators
 */
class Value extends AbstractValidator
{
    /**
     * Шаблоны оприсывающие различные патерны, для проверки поступающих данных.
     * (символы, которые необходимо экранировать: ! $ ( ) * + - . / : < = > ? [ \ ] ^ { | } )
     */
    public const // PATTER_OPERATOR      = "^[a-zA-Z_][a-zA-Z0-9_]*$",
                 PATTER_LOGIN         = "^[a-zA-Z0-9][a-zA-Z0-9\\-.]*?[a-zA-Z0-9]$",
                 // PATTER_LOGIN_NAME    = "^[a-zA-Z0-9][a-zA-Z0-9'\\-\\.,&]*?[a-zA-Z0-9]$",
                 // PATTER_LOGIN_EXTEND  = "^[a-zA-Z0-9][a-zA-Z0-9'\\-\\.,&@]*?[a-zA-Z0-9]$", // LOGIN + NAME + EMAIL
                 // PATTER_REWRITE_NAME  = "^[a-zA-Z0-9][a-zA-Z0-9'\\-_]*[a-zA-Z0-9']$",
                 // PATTER_REWRITE_PATH  = "^[a-zA-Z0-9][a-zA-Z0-9\\-_/]*[a-zA-Z0-9]$",
                 // PATTER_PASSWORD      = "^[a-zA-Z0-9\"'!#$%&()*+,\\-.\\/\\:;<=>?@\\[\\\\\\]\\^_`{|}~]+$",
                 PATTERN_NAME         = "^[^\"!#$%&()*+\\/\\:;<=>?@\\[\\\\\\]\\^_`{|}~]+$", // all characters whithout specials, but with [ ',-.]
                 PATTERN_ENGLISH_NAME = "^[a-zA-Z][a-zA-Z0-9 ',\\-.]*[a-zA-Z]$",
                 PATTERN_ENGLISH_TEXT = "^[a-zA-Z0-9 \t\n\r\"'!#$%&()*+,\\-.\\/\\:;<=>?@\\[\\\\\\]\\^_`{|}~]+$",
                 PATTERN_PHONE        = "^[0-9+][0-9 \\-()]+[0-9]$",
                 PATTERN_PHONE_EXTEND = "^[0-9 +\\-.,()]+$",
                 PATTERN_NOT_SPECIALS = "^[^\"'!#$%&()*+,\\-.\\/\\:;<=>?@\\[\\\\\\]\\^_`{|}~]+$"; // all characters whithout specials

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ public function __construct(array $attrs = [], array $errors = [])
    {
        // добавление атрибута "шаблон значения"
        $this->_attrs['pattern'] = '';

        // массив сообщений об ошибках валидатора
        // соответствующим кодам ошибок по умолчанию
        // $this->_errors[self::INVALID_VALUE] = __args('1fmc2#3c', 'Значение не соответствует формату "%s"', 'pattern');
        $this->_errors[self::INVALID_VALUE] = __args('The indicated value contains invalid characters');

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

        if (preg_match('/' . $this->_getAttr('pattern', $data) . '/i', $data['value']))
        {
            return true;
        }

        $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
        return false;
    }

}