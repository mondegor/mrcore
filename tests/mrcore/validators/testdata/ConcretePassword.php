<?php declare(strict_types=1);
namespace mrcore\validators\testdata;
use mrcore\validators\Password;

require_once 'mrcore/validators/Password.php';

class ConcretePassword extends Password
{

    public function testValidate(array $data, array &$listErrors): bool
    {
        return $this->_validate($data, $listErrors);
    }

}