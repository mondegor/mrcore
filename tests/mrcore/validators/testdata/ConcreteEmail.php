<?php declare(strict_types=1);
namespace mrcore\validators\testdata;
use mrcore\validators\Email;

require_once 'mrcore/validators/Email.php';

class ConcreteEmail extends Email
{

    public function testValidateItem(string $item): bool
    {
        return $this->_validateItem($item);
    }

}