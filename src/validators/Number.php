<?php declare(strict_types=1);
namespace mrcore\validators;

/*
   use mrcore\validators\Number;

   'number' => ['class' => Number::class]

    // OR

    use mrcore\validators\Number;

    ...

    'number' => [
        'class' => Number::class,
        'attrs' => [
            // 'isFloat'  => false,
            // 'minValue' => null,
            // 'maxValue' => null,
        ],
        'errors' => [
            Number::INVALID_VALUE => __targs('Значение может содержать только цифры'),
            Number::INVALID_RANGE => __targs('Значение должно находиться в интервале от %s до %s', 'minValue', 'maxValue'),
            Number::INVALID_VALUE_MIN => __targs('Значение должно быть не меньше %s', 'minValue'),
            Number::INVALID_VALUE_MAX => __targs('Значение должно быть не больше %s', 'maxValue'),
        ],
    ],
*/

/**
 * Валидатор натуральных чисел.
 *
 * @author  Andrey J. Nazarov
 */
class Number extends AbstractValidator
{
    /**
     * Константа точности для сравнения чисел с плавающей запятой.
     */
    private const EPSILON = 0.000001;

    ################################### Properties ###################################

    /**
     * @inheritdoc
     */
    protected int $dataTypes = self::DTYPE_STRING + self::DTYPE_INT + self::DTYPE_FLOAT;

    /**
     * @inheritdoc
     */
    protected array $attrs = array
    (
        'isFloat' => false,
        'minValue' => null,
        'maxValue' => null,
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::INVALID_VALUE] = __targs('Значение может содержать только цифры');
        $this->errors[self::INVALID_RANGE] = __targs('Значение должно находиться в интервале от %s до %s', 'minValue', 'maxValue');
        $this->errors[self::INVALID_VALUE_MIN] = __targs('Значение должно быть не меньше %s', 'minValue');
        $this->errors[self::INVALID_VALUE_MAX] = __targs('Значение должно быть не больше %s', 'maxValue');

        parent::__construct($attrs, $errors);

        assert(is_bool($this->attrs['isFloat']));
        assert(null === $this->attrs['minValue'] || is_int($this->attrs['minValue']) || is_float($this->attrs['minValue']));
        assert(null === $this->attrs['maxValue'] || is_int($this->attrs['maxValue']) || is_float($this->attrs['maxValue']));
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        $value = $data['value'];

        if (is_int($value))
        {
            if ($data['isFloat'])
            {
                $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
                return false;
            }
        }
        else if (is_float($value))
        {
            if (!$data['isFloat'])
            {
                $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
                return false;
            }
        }
        else
        {
            if ($data['isFloat'])
            {
                $value = strtr($value, ',', '.');
            }

            if (preg_match('/^' . ($data['isFloat'] ? '(?(?=-?\d+\.\d+)-?\d+\.\d+|-?\d+)' : '-?\d+') . '$/i', $value) < 1)
            {
                $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
                return false;
            }

            $value = $data['isFloat'] ? (float)$value : (int)$value;
        }

        // :WARNING: float числа необходимо сравнивать следующим образом: ($a - $b) < $epsilon
        if (null !== $data['minValue'] && $value < $data['minValue'] && (!$data['isFloat'] || abs($value - $data['minValue']) > self::EPSILON))
        {
            $this->addErrorByCode(isset($this->errors[self::INVALID_VALUE_MIN]) ? self::INVALID_VALUE_MIN : self::INVALID_RANGE, $data, $listErrors);
            return false;
        }

        if (null !== $data['maxValue'] && $data['maxValue'] < $value && (!$data['isFloat'] || abs($data['maxValue'] - $value) > self::EPSILON))
        {
            $this->addErrorByCode(isset($this->errors[self::INVALID_VALUE_MAX]) ? self::INVALID_VALUE_MAX : self::INVALID_RANGE, $data, $listErrors);
            return false;
        }

        return true;
    }

}