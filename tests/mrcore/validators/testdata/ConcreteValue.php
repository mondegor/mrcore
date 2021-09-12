<?php declare(strict_types=1);
namespace mrcore\validators\testdata;
use mrcore\validators\Value;

require_once 'mrcore/validators/Value.php';

class ConcreteValue extends Value
{

    public function testValidate(array $data, array &$listErrors): bool
    {
        return $this->_validate($data, $listErrors);
    }

}