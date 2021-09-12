<?php declare(strict_types=1);
namespace mrcore\validators\testdata;
use mrcore\validators\NotEmpty;

require_once 'mrcore/validators/NotEmpty.php';

class ConcreteNotEmpty extends NotEmpty
{

    public function testValidate(array $data, array &$listErrors): bool
    {
        return $this->_validate($data, $listErrors);
    }

}