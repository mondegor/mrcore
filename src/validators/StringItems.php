<?php declare(strict_types=1);
namespace mrcore\validators;

/**
 * Абстракция валидации списка однотипных элементов указанных через разделитель.
 *
 * @author  Andrey J. Nazarov
 */
abstract class StringItems extends AbstractValidator
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
        'multy' => false,
        'separator' => ',',
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        parent::__construct($attrs, $errors);

        assert(is_bool($this->attrs['multy']));
        assert(is_string($this->attrs['separator']));
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        $multy = $data['multy'];
        $separator = $data['separator'];

        if ($multy)
        {
            foreach (explode($separator, $data['value']) as $item)
            {
                if (!$this->_validateItem($item))
                {
                    $this->addErrorByCode(self::INVALID_VALUES, $data, $listErrors);
                    return false;
                }
            }
        }
        else
        {
            if (!$this->_validateItem($data['value']))
            {
                $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
                return false;
            }
        }

        return true;
    }

    /**
     * Проверка указанного элемента.
     */
    abstract protected function _validateItem(string $item): bool;

}