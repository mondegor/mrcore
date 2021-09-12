<?php declare(strict_types=1);
namespace mrcore\validators\testdata;
use mrcore\validators\StringItems;

require_once 'mrcore/validators/StringItems.php';

class ConcreteStringItems extends StringItems
{

    public function testValidate(array $data, array &$listErrors): bool
    {
        return $this->_validate($data, $listErrors);
    }

    protected function _validateItem(string $item): bool
    {
        return true;
    }

}