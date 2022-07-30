<?php declare(strict_types=1);
namespace mrcore\validators;

/*
   use mrcore\validators\Value;

   'value' => ['class' => Value::class, 'pattern' => Value::PATTERN_*]

    // OR

    use mrcore\validators\Value;

    ...

    'value' => [
        'class' => Value::class,
        'attrs' => [
            'pattern' => Value::PATTERN_*,
        ],
        'errors' => [
            Value::INVALID_VALUE => __targs('Значение не соответствует формату "%s"', 'pattern'),
        ],
    ],
*/

/**
 * Проверка на соответствие значения с заданным шаблоном,
 * а также проверка длины значения.
 *
 * @author  Andrey J. Nazarov
 */
class Value extends AbstractValidator
{
    /**
     * Шаблоны оприсывающие различные патерны, для проверки поступающих данных.
     * (символы, которые необходимо экранировать: ! $ ( ) * + - . / : < = > ? [ \ ] ^ { | } )
     */
    public const PATTERN_LOGIN        = "^[a-zA-Z0-9][a-zA-Z0-9\\-.]*?[a-zA-Z0-9]$",
                 // PATTERN_IP           = "^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$",
                 PATTERN_OPERATOR     = "^[a-zA-Z_][a-zA-Z0-9_]*$",
                 PATTERN_TAG          = "^[a-zA-Z][a-zA-Z0-9\\-_]*$",
                 PATTERN_NAME         = "^[^\"!#$%&()*+\\/\\:;<=>?@\\[\\\\\\]\\^_`{|}~]+$", // all characters whithout specials, but with [ ',-.]
                 PATTERN_ENGLISH_NAME = "^[a-zA-Z][a-zA-Z0-9 ',\\-.]*[a-zA-Z]$",
                 PATTERN_ENGLISH_TEXT = "^[a-zA-Z0-9 \t\n\r\"'!#$%&()*+,\\-.\\/\\:;<=>?@\\[\\\\\\]\\^_`{|}~]+$",
                 // PATTERN_PHONE        = "^[0-9+][0-9 \\-()]+[0-9]$",
                 // PATTERN_PHONE_EXTEND = "^[0-9 +\\-.,()]+$",
                 PATTERN_NOT_SPECIALS = "^[^\"'!#$%&()*+,\\-.\\/\\:;<=>?@\\[\\\\\\]\\^_`{|}~]+$"; // all characters whithout specials

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
        'pattern' => null, // добавление атрибута "шаблон значения"
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        // $this->errors[self::INVALID_VALUE] = __targs('Значение не соответствует формату "%s"', 'pattern');
        $this->errors[self::INVALID_VALUE] = __targs('The indicated value contains invalid characters');

        parent::__construct($attrs, $errors);

        assert(is_string($this->attrs['pattern']));
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        if (preg_match('/' . $data['pattern'] . '/i', $data['value']) > 0)
        {
            return true;
        }

        $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
        return false;
    }

}