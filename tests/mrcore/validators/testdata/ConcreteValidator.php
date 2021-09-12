<?php declare(strict_types=1);
namespace mrcore\validators\testdata;
use mrcore\validators\AbstractValidator;

require_once 'mrcore/validators/AbstractValidator.php';

class ConcreteValidator extends AbstractValidator
{

    protected array $_attrs = ['attr1' => 'value1'];
    protected array $_errors = [1000 => 'error1'];

    protected function _validate(array $data, array &$listErrors): bool
    {
        return true;
    }

    public function testGetAttr(string $name, array &$data)
    {
        return $this->_getAttr($name, $data);
    }

    public function testMakeArgForMessage(string &$name, array $data): bool
    {
        return $this->_makeArgForMessage($name, $data);
    }

    public function testGetErrorMessage($error, array $data)
    {
        return $this->_getErrorMessage($error, $data);
    }

}