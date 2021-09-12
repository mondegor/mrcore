<?php declare(strict_types=1);
namespace mrcore\validators\testdata;
use mrcore\validators\Length;

require_once 'mrcore/validators/Length.php';

class ConcreteLength extends Length
{

    public function testValidate(array $data, array &$listErrors): bool
    {
        return $this->_validate($data, $listErrors);
    }

}