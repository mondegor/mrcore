<?php declare(strict_types=1);
namespace mrcore\validators;

/*
    use mrcore\validators\IpAddress;

    'ipaddress' => ['class' => IpAddress::class]

    // OR

    use mrcore\validators\IpAddress;

    ...

    'ipaddress' => [
        'class' => IpAddress::class,
        'attrs' => [
            // 'multy' => false,
            // 'separator' => ',',
        ],
        'errors' => [
            IpAddress::INVALID_VALUE => __targs('Указанное значение не является IP адресом'),
            IpAddress::INVALID_VALUES => __targs('Указанное значение должно содержать IP адреса разделёные знаком "%s"', 'separator'),
        ],
    ],
*/

/**
 * Валидатор ip адресов.
 *
 * @author  Andrey J. Nazarov
 */
class IpAddress extends StringItems
{
    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::INVALID_VALUE] = __targs('Указанное значение не является IP адресом');
        $this->errors[self::INVALID_VALUES] = __targs('Указанное значение должно содержать IP адреса разделёные знаком "%s"', 'separator');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    protected function _validateItem(string $item): bool
    {
        // IP не должен быть короче 7 символов и длинее 15 символов
        $item = trim($item);
        $length = strlen($item);

        return ($length > 6 && $length < 15 && false !== filter_var($item, FILTER_VALIDATE_IP));
    }

}