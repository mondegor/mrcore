<?php declare(strict_types=1);
namespace mrcore\validators;

/*
    use mrcore\validators\Length;

    'length' => ['class' => Length::class]

    // OR

    use mrcore\validators\AbstractValidator;

    ...

    'length' => [
        'class' => Length::class,
        'attrs' => [
            // 'minLength' => 0,
            // 'maxLength' => null,
            // 'showFullError' => false,
        ],
        'errors' => [
            Length::INVALID_LENGTH => __targs('Значение должно быть не короче %u символов и не длиннее %u символов', 'minLength', 'maxLength'),
            // Length::INVALID_LENGTH_EQUAL => __targs('Значение должно быть длиной ровно в %u символов', 'maxLength'),
            // Length::INVALID_LENGTH_MIN => __targs('Значение должно быть не короче %u символов', 'minLength'),
            // Length::INVALID_LENGTH_MAX => __targs('Значение должно быть не длиннее %u символов', 'maxLength')
        ],
    ],
*/

/**
 * Валидатор значений.
 *
 * @author  Andrey J. Nazarov
 */
class Length extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected int $dataTypes = self::DTYPE_STRING;

    /**
     * @inheritdoc
     */
    protected array $attrs = array
    (
        'minLength' => 0,
        'maxLength' => null,
        'showFullError' => false, // отображать сообщение INVALID_LENGTH если указаны и minLength и maxLength
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::INVALID_LENGTH]       = __targs('Значение должно быть не короче %u символов и не длиннее %u символов', 'minLength', 'maxLength');
        $this->errors[self::INVALID_LENGTH_EQUAL] = __targs('Значение должно быть длиной ровно в %u символов', 'maxLength');
        $this->errors[self::INVALID_LENGTH_MIN]   = __targs('Значение должно быть не короче %u символов', 'minLength');
        $this->errors[self::INVALID_LENGTH_MAX]   = __targs('Значение должно быть не длиннее %u символов', 'maxLength');

        parent::__construct($attrs, $errors);

        assert(is_int($this->attrs['minLength']));
        assert(is_int($this->attrs['maxLength']) && $this->attrs['maxLength'] > 0);
        assert($this->attrs['maxLength'] >= $this->attrs['minLength']);
        assert(is_bool($this->attrs['showFullError']));
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        assert($data['maxLength'] > 0 && $data['maxLength'] >= $data['minLength']);

        $length = mb_strlen($data['value']);

        // если необходимо точное соответствие длины
        if ($data['minLength'] === $data['maxLength'] && $length !== $data['maxLength'])
        {
            $this->addErrorByCode(self::INVALID_LENGTH_EQUAL, $data, $listErrors);
            return false;
        }

        if ($length > $data['maxLength'])
        {
            $this->addErrorByCode($this->attrs['showFullError'] && $data['minLength'] > 0 ? self::INVALID_LENGTH : self::INVALID_LENGTH_MAX, $data, $listErrors);
            return false;
        }

        if ($data['minLength'] > $length)
        {
            $this->addErrorByCode($this->attrs['showFullError'] ? self::INVALID_LENGTH : self::INVALID_LENGTH_MIN, $data, $listErrors);
            return false;
        }

        return true;
    }

}