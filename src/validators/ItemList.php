<?php declare(strict_types=1);
namespace mrcore\validators;

/*
    use mrcore\validators\ItemList;

    'itemlist' => ['class' => ItemList::class]

    // OR

    use mrcore\validators\ItemList;

    ...

    'itemlist' => [
        'class' => ItemList::class,
        'attrs' => [
            'checkKeys' => false,
            'items' => null,
        ],
        'errors' => [
            ItemList::INVALID_VALUE  => __targs('Указано неизвестное значение "%s"', 'value'),
        ]
    ],
*/

/**
 * Валидатор контролирует чтобы все указанное значение содержалось в заданном списке.
 *
 * @author  Andrey J. Nazarov
 */
class ItemList extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected int $dataTypes = self::DTYPE_STRING + self::DTYPE_INT;

    /**
     * @inheritdoc
     */
    protected array $attrs = array
    (
        'checkKeys' => false, // при true проверять ключи массива, при false проверять значения массива
        'items' => null,
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::INVALID_VALUE] = __targs('Указано неизвестное значение "%s"', 'value');

        parent::__construct($attrs, $errors);

        assert(is_array($this->attrs['items']));
        assert(!empty($this->attrs['items']), 'В массиве items не было указано ни одного элемента');
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        if (!($data['checkKeys'] ? isset($data['items'][$data['value']]) : in_array($data['value'], $data['items'], true)))
        {
            $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
            return false;
        }

        return true;
    }

}