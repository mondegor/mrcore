<?php /* [MrLocate: group: common2v; module: validators] */
namespace mrcore\validators;

require_once 'mrcore/validators/AbstractValidator.php';

/**
 * Валидатор списка однотипных элементов указанных через разделитель.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/validators
 */
abstract class StringItems extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    /*__override__*/ protected array $_attrs = array
    (
        'isMulty' => false,
        'separator' => ',',
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _validate(array $data, array &$listErrors): bool
    {
        if ('' === $data['value'])
        {
            return true;
        }

        $isMulty = $this->_getAttr('isMulty', $data);
        $separator = $this->_getAttr('separator', $data);

        if ($isMulty)
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
     *
     * @param      string  $item
     * @return     bool
     */
    abstract protected function _validateItem(string $item): bool;

}